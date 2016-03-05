<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\Infobip;

use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;

/**
 * Base class for Infobip REST API response handling classes.
 */
class InfobipResponseHandlerBase {

  /**
   * Maps a given message status to the corresponding standard delivery status.
   *
   * @param string[] $status
   *   The message status array containing the message status info.
   *
   * @return int
   *   The properly mapped message delivery status.
   */
  protected function mapStatus(array $status) {
    if (isset(self::$responseStatus[$status['id']]['map_to'])) {
      $mapped_status = self::$responseStatus[$status['id']]['map_to'];
    }
    else {
      $mapped_status = self::$deliveryStatusMap[$status['groupId']];
    }
    return $mapped_status;
  }

  /**
   * Maps the message's error codes to corresponding standard gateway error.
   *
   * @param array $error
   *   The message error array containing the message error info.
   *
   * @return array
   *   An array containing information on the error. The error object with error
   *   code (number or text) and error description if there is one.
   */
  protected function parseError(array $error) {
    if (isset($error)) {
      if (isset(self::$responseStatus[$error['id']]['map_to'])) {
        $mapped_error = self::$responseErrors[$error['id']]['map_to'];
      }
      else {
        $mapped_error = self::$errorMap[$error['groupId']];
      }
      return [
        'error_code' => $mapped_error,
        // @todo: Should we standardize the error messages?
        'error_message' => $error['description'],
        'gateway_error_code' => $error['id'],
        'gateway_error_message' => $error['description'],
      ];
    }
    else {
      return [
        'error_code' => 0,
        'error_message' => '',
        'gateway_error_code' => '',
        'gateway_error_message' => '',
      ];
    }
  }

  /**
   * Error codes and messages that are generated by the Infobip gateway.
   */

  /**
   * Mapping of Infobip's delivery status to SMS Framework's delivery status.
   *
   * @var array
   */
  protected static $deliveryStatusMap = [
    '0' => SmsDeliveryReportInterface::STATUS_SENT,
    '1' => SmsDeliveryReportInterface::STATUS_PENDING,
    '2' => SmsDeliveryReportInterface::STATUS_NOT_DELIVERED,
    '3' => SmsDeliveryReportInterface::STATUS_DELIVERED,
    '4' => SmsDeliveryReportInterface::STATUS_EXPIRED,
    '5' => SmsDeliveryReportInterface::STATUS_REJECTED,
  ];

  /**
   * List of high level delivery status groups from the Infobip gateway.
   *
   * @var array
   */
  protected static $statusGroups = [
    '0' => ['ACCEPTED' => 'Message is accepted.'],
    '1' => ['PENDING' => 'Message is in pending status.'],
    '2' => ['UNDELIVERABLE' => 'Message is undeliverable.'],
    '3' => ['DELIVERED' => 'Message is delivered.'],
    '4' => ['EXPIRED' => 'Message is expired.'],
    '5' => ['REJECTED' => 'Message is rejected.'],
  ];

  /**
   * List of Infobip gateway response status.
   *
   * @var array
   */
  protected static $responseStatus = [
    // Group 0: ACCEPTED.
    '0' => [
      'name' => 'MESSAGE_ACCEPTED',
      'description' => 'Message accepted',
      'group_id' => '0',
    ],

    // Group 1: PENDING.
    '1' => [
      'name' => 'PENDING_TIME_VIOLATION',
      'description' => 'Time window violation',
      'group_id' => '1',
    ],
    '3' => [
      'name' => 'PENDING_WAITING_DELIVERY',
      'description' => 'Message sent, waiting for delivery report',
      'group_id' => '1',
    ],
    '7' => [
      'name' => 'PENDING_ENROUTE',
      'description' => 'Message sent to next instance',
      'group_id' => '1',
    ],
    '26' => [
      'name' => 'PENDING_ACCEPTED',
      'description' => 'Pending Accepted',
      'group_id' => '1',
    ],
    '27' => [
      'name' => 'PENDING_APPROVAL',
      'description' => 'Pending Approval',
      'group_id' => '1',
    ],
    
    // Group 2: UNDELIVERABLE.
    '4' => [
      'name' => 'UNDELIVERABLE_REJECTED_OPERATOR',
      'description' => 'Message rejected by operator',
      'group_id' => '2',
    ],
    '9' => [
      'name' => 'UNDELIVERABLE_NOT_DELIVERED',
      'description' => 'Message sent not delivered',
      'group_id' => '2',
    ],
    '31' => [
      'name' => 'UNDELIVERABLE_NOT_SENT',
      'description' => 'Message not sent',
      'group_id' => '2',
    ],

    // Group 3: DELIVERED.
    '5' => [
      'name' => 'DELIVERED_TO_HANDSET',
      'description' => 'Message delivered to handset',
      'group_id' => '3',
    ],
    '2' => [
      'name' => 'DELIVERED_TO_OPERATOR',
      'description' => 'Message delivered to operator',
      'group_id' => '3',
    ],
    '30' => [
      'name' => 'DELIVERED',
      'description' => 'MO forwarded action completed',
      'group_id' => '3',
    ],

    // Group 4: EXPIRED.
    '15' => [
      'name' => 'EXPIRED_EXPIRED',
      'description' => 'Message expired',
      'group_id' => '4',
    ],
    '22' => [
      'name' => 'EXPIRED_UNKNOWN',
      'description' => 'Unknown Reason',
      'group_id' => '4',
    ],
    '29' => [
      'name' => 'EXPIRED_DLR_UNKNOWN',
      'description' => 'Expired DLR Unknown',
      'group_id' => '4',
    ],
    
    // Group 5: REJECTED.
    '6' => [
      'name' => 'REJECTED_NETWORK',
      'description' => 'Network is forbidden',
      'group_id' => '5',
    ],
    '8' => [
      'name' => 'REJECTED_PREFIX_MISSING',
      'description' => 'Number prefix missing',
      'group_id' => '5',
    ],
    '10' => [
      'name' => 'REJECTED_DND',
      'description' => 'Destination on DND list',
      'group_id' => '5',
    ],
    '11' => [
      'name' => 'REJECTED_SOURCE',
      'description' => 'Invalid Source address',
      'group_id' => '5',
      'map_to' => SmsDeliveryReportInterface::STATUS_INVALID_SENDER,
    ],
    '12' => [
      'name' => 'REJECTED_NOT_ENOUGH_CREDITS',
      'description' => 'Not enough credits',
      'group_id' => '5',
    ],
    '13' => [
      'name' => 'REJECTED_SENDER',
      'description' => 'By Sender',
      'group_id' => '5',
    ],
    '14' => [
      'name' => 'REJECTED_DESTINATION',
      'description' => 'By Destination',
      'group_id' => '5',
    ],
    '16' => [
      'name' => 'REJECTED_NOT_REACHABLE',
      'description' => 'Network not reachable',
      'group_id' => '5',
    ],
    '17' => [
      'name' => 'REJECTED_PREPAID_PACKAGE_EXPIRED',
      'description' => 'Prepaid package expired',
      'group_id' => '5',
    ],
    '18' => [
      'name' => 'REJECTED_DESTINATION_NOT_REGISTERED',
      'description' => 'Destination not registered',
      'group_id' => '5',
    ],
    '19' => [
      'name' => 'REJECTED_ROUTE_NOT_AVAILABLE',
      'description' => 'Route not available',
      'group_id' => '5',
    ],
    '20' => [
      'name' => 'REJECTED_FLOODING_FILTER',
      'description' => 'Rejected flooding',
      'group_id' => '5',
    ],
    '21' => [
      'name' => 'REJECTED_SYSTEM_ERROR',
      'description' => 'System error',
      'group_id' => '5',
    ],
    '23' => [
      'name' => 'REJECTED_DUPLICATE_MESSAGE_ID',
      'description' => 'Rejected duplicate message ID',
      'group_id' => '5',
    ],
    '24' => [
      'name' => 'REJECTED_INVALID_UDH',
      'description' => 'Rejected invalid UDH',
      'group_id' => '5',
    ],
    '25' => [
      'name' => 'REJECTED_MESSAGE_TOO_LONG',
      'description' => 'Rejected message too long',
      'group_id' => '5',
    ],
    '28' => [
      'name' => 'REJECTED_NOT_SENT',
      'description' => 'Rejected Not Sent',
      'group_id' => '5',
    ],
    '51' => [
      'name' => 'MISSING_TO',
      'description' => 'Missing destination',
      'group_id' => '5',
      'map_to' => SmsDeliveryReportInterface::STATUS_INVALID_RECIPIENT,
    ],
    '52' => [
      'name' => 'REJECTED_DESTINATION',
      'description' => 'Invalid destination address',
      'group_id' => '5',
      'map_to' => SmsDeliveryReportInterface::STATUS_INVALID_RECIPIENT,
    ],
  ];

  /**
   * Error map to SmsGatewayPluginInterface error status codes.
   *
   * @var int[]
   */
  protected static $errorMap = [
    '0' => SmsGatewayPluginInterface::STATUS_OK,
    '1' => SmsGatewayPluginInterface::STATUS_ERR_OTHER,
    '2' => SmsGatewayPluginInterface::STATUS_ERR_OTHER,
    '3' => SmsGatewayPluginInterface::STATUS_ERR_OTHER,
  ];

  protected static $errorGroups = [
    '0' => ['OK' => 'No error.'],
    '1' => ['HANDSET_ERRORS' => 'Handset error occurred.'],
    '2' => ['USER_ERRORS' => 'User error occurred.'],
    '3' => ['OPERATOR_ERRORS' => 'Operator error occurred.'],
  ];

  protected static $responseErrors = [
    // Group 0: OK.
    '0' => [
      'name' => 'NO_ERROR',
      'description' => 'No Error',
      'is_permanent' => FALSE,
      'group_id' => '0',
    ],
    '5000' => [
      'name' => 'VOICE_ANSWERED',
      'description' => 'Call answered by human',
      'is_permanent' => TRUE,
      'group_id' => '0',
    ],
    '5001' => [
      'name' => 'VOICE_ANSWERED_MACHINE',
      'description' => 'Call answered by machine',
      'is_permanent' => TRUE,
      'group_id' => '0',
    ],
  // Group 1: HANDSET_ERRORS.
    '1' => [
      'name' => 'EC_UNKNOWN_SUBSCRIBER',
      'description' => 'Unknown Subscriber',
      'is_permanent' => TRUE,
      'group_id' => '1',
    ],
    '5' => [
      'name' => 'EC_UNIDENTIFIED_SUBSCRIBER',
      'description' => 'Unidentified Subscriber',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '6' => [
      'name' => 'EC_ABSENT_SUBSCRIBER_SM',
      'description' => 'Absent Subscriber',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '9' => [
      'name' => 'EC_ILLEGAL_SUBSCRIBER',
      'description' => 'Illegal Subscriber',
      'is_permanent' => TRUE,
      'group_id' => '1',
    ],
    '11' => [
      'name' => 'EC_TELESERVICE_NOT_PROVISIONED',
      'description' => 'Teleservice Not Provisioned',
      'is_permanent' => TRUE,
      'group_id' => '1',
    ],
    '12' => [
      'name' => 'EC_ILLEGAL_EQUIPMENT',
      'description' => 'Illegal Equipment',
      'is_permanent' => TRUE,
      'group_id' => '1',
    ],
    '13' => [
      'name' => 'EC_CALL_BARRED',
      'description' => 'Call Barred',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '21' => [
      'name' => 'EC_FACILITY_NOT_SUPPORTED',
      'description' => 'Facility Not Supported',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '27' => [
      'name' => 'EC_ABSENT_SUBSCRIBER',
      'description' => 'Absent Subscriber',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '31' => [
      'name' => 'EC_SUBSCRIBER_BUSY_FOR_MT_SMS',
      'description' => 'Subscriber Busy For Mt SMS',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '32' => [
      'name' => 'EC_SM_DELIVERY_FAILURE',
      'description' => 'SM Delivery Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '33' => [
      'name' => 'EC_MESSAGE_WAITING_LIST_FULL',
      'description' => 'Message Waiting List Full',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '34' => [
      'name' => 'EC_SYSTEM_FAILURE',
      'description' => 'System Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '35' => [
      'name' => 'EC_DATA_MISSING',
      'description' => 'Data Missing',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '36' => [
      'name' => 'EC_UNEXPECTED_DATA_VALUE',
      'description' => 'Unexpected Data Value',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '72' => [
      'name' => 'EC_USSD_BUSY',
      'description' => 'Ussd Busy',
      'is_permanent' => TRUE,
      'group_id' => '1',
    ],
    '255' => [
      'name' => 'EC_UNKNOWN_ERROR',
      'description' => 'Unknown Error',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '256' => [
      'name' => 'EC_SM_DF_MEMORYCAPACITYEXCEEDED',
      'description' => 'SM DF Memory Capacity Exceeded',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '257' => [
      'name' => 'EC_SM_DF_EQUIPMENTPROTOCOLERROR',
      'description' => 'SM DF Equipment Protocol Error',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '258' => [
      'name' => 'EC_SM_DF_EQUIPMENTNOTSM_EQUIPPED',
      'description' => 'SM DF Equipment Not SM Equipped',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '259' => [
      'name' => 'EC_SM_DF_UNKNOWNSERVICECENTRE',
      'description' => 'SM DF Unknown Service Centre',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '260' => [
      'name' => 'EC_SM_DF_SC_CONGESTION',
      'description' => 'SM DF Sc Congestion',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '261' => [
      'name' => 'EC_SM_DF_INVALIDSME_ADDRESS',
      'description' => 'SM DF InvalidSME Address',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '262' => [
      'name' => 'EC_SM_DF_SUBSCRIBERNOTSC_SUBSCRIBER',
      'description' => 'SM DF Subscribernotsc Subscriber',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '500' => [
      'name' => 'EC_PROVIDER_GENERAL_ERROR',
      'description' => 'Provider General Error',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '502' => [
      'name' => 'EC_NO_RESPONSE',
      'description' => 'No Response',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '503' => [
      'name' => 'EC_SERVICE_COMPLETION_FAILURE',
      'description' => 'Service Completion Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '504' => [
      'name' => 'EC_UNEXPECTED_RESPONSE_FROM_PEER',
      'description' => 'Unexpected Response From Peer',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '507' => [
      'name' => 'EC_MISTYPED_PARAMETER',
      'description' => 'Mistyped Parameter',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '508' => [
      'name' => 'EC_NOT_SUPPORTED_SERVICE',
      'description' => 'Supported Service',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '509' => [
      'name' => 'EC_DUPLICATED_INVOKE_ID',
      'description' => 'Duplicated Invoke Id',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '511' => [
      'name' => 'EC_INITIATING_RELEASE',
      'description' => 'Initiating Release',
      'is_permanent' => TRUE,
      'group_id' => '1',
    ],
    '1024' => [
      'name' => 'EC_OR_APPCONTEXTNOTSUPPORTED',
      'description' => 'App Context Not Supported',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1025' => [
      'name' => 'EC_OR_INVALIDDESTINATIONREFERENCE',
      'description' => 'Invalid Destination Reference',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1026' => [
      'name' => 'EC_OR_INVALIDORIGINATINGREFERENCE',
      'description' => 'Invalid Originating Reference',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1027' => [
      'name' => 'EC_OR_ENCAPSULATEDAC_NOTSUPPORTED',
      'description' => 'Encapsulated AC Not Supported',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1028' => [
      'name' => 'EC_OR_TRANSPORTPROTECTIONNOTADEQUATE',
      'description' => 'Transport Protection Not Adequate',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1029' => [
      'name' => 'EC_OR_NOREASONGIVEN',
      'description' => 'No Reason Given',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1030' => [
      'name' => 'EC_OR_POTENTIALVERSIONINCOMPATIBILITY',
      'description' => 'Potential Version Incompatibility',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1031' => [
      'name' => 'EC_OR_REMOTENODENOTREACHABLE',
      'description' => 'Remote Node Not Reachable',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1152' => [
      'name' => 'EC_NNR_NOTRANSLATIONFORANADDRESSOFSUCHNATURE',
      'description' => 'No Translation For An Address Of Such Nature',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1153' => [
      'name' => 'EC_NNR_NOTRANSLATIONFORTHISSPECIFICADDRESS',
      'description' => 'No Translation For This Specific Address',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1154' => [
      'name' => 'EC_NNR_SUBSYSTEMCONGESTION',
      'description' => 'Subsystem Congestion',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1155' => [
      'name' => 'EC_NNR_SUBSYSTEMFAILURE',
      'description' => 'Subsystem Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1156' => [
      'name' => 'EC_NNR_UNEQUIPPEDUSER',
      'description' => 'Unequipped User',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1157' => [
      'name' => 'EC_NNR_MTPFAILURE',
      'description' => 'MTP Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1158' => [
      'name' => 'EC_NNR_NETWORKCONGESTION',
      'description' => 'Network Congestion',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1159' => [
      'name' => 'EC_NNR_UNQUALIFIED',
      'description' => 'Unqualified',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1160' => [
      'name' => 'EC_NNR_ERRORINMESSAGETRANSPORTXUDT',
      'description' => 'Error In Message Transport XUDT',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1161' => [
      'name' => 'EC_NNR_ERRORINLOCALPROCESSINGXUDT',
      'description' => 'Error In Local Processing XUDT',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1162' => [
      'name' => 'EC_NNR_DESTINATIONCANNOTPERFORMREASSEMBLYXUDT',
      'description' => 'Destination Cannot Perform Reassembly XUDT',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1163' => [
      'name' => 'EC_NNR_SCCPFAILURE',
      'description' => 'SCCP Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1164' => [
      'name' => 'EC_NNR_HOPCOUNTERVIOLATION',
      'description' => 'Hop Counter Violation',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1165' => [
      'name' => 'EC_NNR_SEGMENTATIONNOTSUPPORTED',
      'description' => 'Segmentation Not Supported',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1166' => [
      'name' => 'EC_NNR_SEGMENTATIONFAILURE',
      'description' => 'Segmentation Failure',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1281' => [
      'name' => 'EC_UA_USERSPECIFICREASON',
      'description' => 'User Specific Reason',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1282' => [
      'name' => 'EC_UA_USERRESOURCELIMITATION',
      'description' => 'User Resource Limitation',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1283' => [
      'name' => 'EC_UA_RESOURCEUNAVAILABLE',
      'description' => 'Resource Unavailable',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1284' => [
      'name' => 'EC_UA_APPLICATIONPROCEDURECANCELLATION',
      'description' => 'Application Procedure Cancellation',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1536' => [
      'name' => 'EC_PA_PROVIDERMALFUNCTION',
      'description' => 'Provider Malfunction',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1537' => [
      'name' => 'EC_PA_SUPPORTINGDIALOGORTRANSACTIONREALEASED',
      'description' => 'Supporting Dialog Or Transaction Realeased',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1538' => [
      'name' => 'EC_PA_RESSOURCELIMITATION',
      'description' => 'Ressource Limitation',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1539' => [
      'name' => 'EC_PA_MAINTENANCEACTIVITY',
      'description' => 'Maintenance Activity',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1540' => [
      'name' => 'EC_PA_VERSIONINCOMPATIBILITY',
      'description' => 'Version Incompatibility',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1541' => [
      'name' => 'EC_PA_ABNORMALMAPDIALOG',
      'description' => 'Abnormal Map Dialog',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1792' => [
      'name' => 'EC_NC_ABNORMALEVENTDETECTEDBYPEER',
      'description' => 'Abnormal Event Detected By Peer',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1793' => [
      'name' => 'EC_NC_RESPONSEREJECTEDBYPEER',
      'description' => 'Response Rejected By Peer',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1794' => [
      'name' => 'EC_NC_ABNORMALEVENTRECEIVEDFROMPEER',
      'description' => 'Abnormal Event Received From Peer',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1795' => [
      'name' => 'EC_NC_MESSAGECANNOTBEDELIVEREDTOPEER',
      'description' => 'Message Cannot Be Delivered To Peer',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],
    '1796' => [
      'name' => 'EC_NC_PROVIDEROUTOFINVOKE',
      'description' => 'Provider Out Of Invoke',
      'is_permanent' => FALSE,
      'group_id' => '1',
    ],

    // Group 2: USER_ERRORS.
    '2049' => [
      'name' => 'EC_IMSI_BLACKLISTED',
      'description' => 'IMSI blacklisted',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '4096' => [
      'name' => 'EC_INVALID_PDU_FORMAT',
      'description' => 'Invalid PDU Format',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '4100' => [
      'name' => 'EC_MESSAGE_CANCELED',
      'description' => 'Message canceled',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '4101' => [
      'name' => 'EC_VALIDITYEXPIRED',
      'description' => 'Validity Expired',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '5002' => [
      'name' => 'EC_VOICE_USER_BUSY',
      'description' => 'User was busy during call attempt',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '5003' => [
      'name' => 'EC_VOICE_NO_ANSWER',
      'description' => 'User was notified, but did not answer call',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '5004' => [
      'name' => 'EC_VOICE_ERROR_DOWNLOADING_FILE',
      'description' => 'File provided for call could not be downloaded',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],
    '5005' => [
      'name' => 'EC_VOICE_ERROR_UNSUPPORTED_AUDIO_FORMAT',
      'description' => 'Format of file provided for call is not supported',
      'is_permanent' => TRUE,
      'group_id' => '2',
    ],

    // Group 3: OPERATOR_ERRORS.
    '10' => [
      'name' => 'EC_BEARER_SERVICE_NOT_PROVISIONED',
      'description' => 'Bearer Service Not Provisioned',
      'is_permanent' => TRUE,
      'group_id' => '3',
    ],
    '20' => [
      'name' => 'EC_SS_INCOMPATIBILITY',
      'description' => 'SS Incompatibility',
      'is_permanent' => FALSE,
      'group_id' => '3',
    ],
    '501' => [
      'name' => 'EC_INVALID_RESPONSE_RECEIVED',
      'description' => 'Invalid Response Received',
      'is_permanent' => FALSE,
      'group_id' => '3',
    ],
    '2050' => [
      'name' => 'EC_DEST_ADDRESS_BLACKLISTED',
      'description' => 'DND blacklisted',
      'is_permanent' => TRUE,
      'group_id' => '3',
    ],
    '2051' => [
      'name' => 'EC_INVALIDMSCADDRESS',
      'description' => 'Text blacklisted',
      'is_permanent' => FALSE,
      'group_id' => '3',
    ],
    '51' => [
      'name' => 'EC_RESOURCE_LIMITATION',
      'description' => 'Resource Limitation',
      'is_permanent' => TRUE,
      'group_id' => '3',
    ],
    '71' => [
      'name' => 'EC_UNKNOWN_ALPHABET',
      'description' => 'Unknown Alphabet',
      'is_permanent' => FALSE,
      'group_id' => '3',
    ],
    '4097' => [
      'name' => 'EC_NOTSUBMITTEDTOGMSC',
      'description' => 'Not Submitted To GMSC',
      'is_permanent' => FALSE,
      'group_id' => '3',
    ],
    '2048' => [
      'name' => 'EC_TIME_OUT',
      'description' => 'Time Out',
      'is_permanent' => FALSE,
      'group_id' => '3',
    ],
    '4102' => [
      'name' => 'EC_NOTSUBMITTEDTOSMPPCHANNEL',
      'description' => 'Not Submitted To Smpp Channel',
      'is_permanent' => TRUE,
      'group_id' => '3',
    ],
  ];
  
}
