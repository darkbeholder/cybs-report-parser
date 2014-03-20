<?php
/**
 * Cybersource Daily Reports Parser
 *
 * @Author Nick Mather
 * @Copyright 2011-2014 Symantec Corporation
 */
namespace Reports;

require_once('config.php');
require_once(LIB_PATH . 'database.php');
require_once(LIB_PATH . 'database_tables.php');
require_once(LIB_PATH . 'parser.php');

//Works just like array_combine but returns false instead of ERR_WARN.
function combine_array($keys = array(), $values = array())
{
    if (!is_array($keys) || !is_array($values))
    {
        return false;
    }
    $combined = array();
    foreach ($keys as $key => $value)
    {
        $combined[trim($value)] = trim($values[$key]);
    }
    return $combined;
}

//Setup the DB connection
$db = Database::getInstance();

$directory = '/home/account/cybs/'; //Path has been changed to protect privilaged data
$directory_done = $directory . 'done/';
$file = $argv[1];
$filename = $directory . $file;

if (!is_file($filename) || ($file == '.' || $file == '..'))
{
    exit;
}

// check for extension
$extension = strtolower(substr($file, -4));
// We only want to process the csv
if ($extension != '.csv')
{
    exit;
}

echo "\nProcessing report file '" . $file . "' ... \n";

$date_file = substr($file, strrpos($file, '_') + 1);
$report_date = substr($date_file,0,4) .'-'.substr($date_file,4,2) .'-'.substr($date_file,6,2);
unset($date_file);

//Fetch the FXrates for the report date
$sql = 'select date,currency1,origfxrate from ' . CYBS_RATES . ' where date = "' . $report_date . '" and currency2 = "USD"';
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $rate)
{ 
    $rates[$rate['date']][$rate['currency1']]=$rate['origfxrate'];
}
        
// Clean up bad data if reprocessing a file
unset($sql);
$sql = 'DELETE FROM ' . CYBS_EXPORT . " WHERE report_file = '{$file}'";
$db->query($sql);
unset($sql);
$sql = 'DELETE FROM ' . CYBS_EXCEPTIONS . " WHERE report_file = '{$file}'";
$db->query($sql);
unset($sql);

//Initialise variables
$sales = $refunds = $chargebacks = 0;
$report_amounts = array();

//Open the report file for reading        
$report = fopen($filename, 'r');
//The first row of the report files has the report name as the first and only field
$report_type = fgetcsv($report, 2000);
$report_type = $report_type[0];
//The second row of the report has the field titles and we will use these as the keys in our data array
$fields = fgetcsv($report, 2000);

//Loop through the remaining rows in the report file and process them
while (($data = combine_array($fields,fgetcsv($report, 2000))) !== false)
{
    //Elabs orders have the format EL<storeid>-<orderid> while pctools orders just have the orderid 
	//Check if a - is present in the merchant_ref_number field and choose the parser based on this
	if (strpos($data['merchant_ref_number'],'-') !== false)
    {
        $parser = Parser::create('Elabs', $db, $data);
        $parser_type = 'elabs';
    }
    else
    {
        $parser = Parser::create('Pctools', $db, $data);
        $parser_type = 'pctools';
    }
    
    $batch_date = $data['batch_date'];
    $order_total = $data['amount'];
    $record_payment_currency = $data['currency'];

	//Determine if the row is a Sale or Refund.
    switch ($data['transaction_type'])
    {
        case 'ics_bill':
            $order_type = 'S';
            break;
        case 'ics_credit':
            $order_type = 'R';
            break;
        default: 
            $order_type = false;
    }
    
    if (!$order_type)
    {
        //The row has an unknown transaction type so output an error and skip to the next row in the file
		echo "UNKNOWN TRANSACTION TYPE '{$ordertype}' for ORDER {$data['merchant_ref_number']}\n";
        continue;
    }
     
	//Determine the origin of the order based on the merchant used for processing
	//Sales are through CYBS origin and Automatic Renewals are through CYBS_AR origin
    switch ($data['merchant_id'])
    {
        //Section modified to protect privilaged data but still show structure
		case 'merchant1':
        case 'merchant2':
        case 'merchant3':
            $origin = 'CYBS';
            break;
        case 'merchant_renew1':
        case 'merchant_renew2':
        case 'merchant_renew3':
        case 'merchant':
            $origin = 'CYBS_AR';
            break;
        default:
            echo "UNABLE TO DETERMINE ORIGIN FOR ORDER {$data['merchant_ref_number']}. '{$data['merchant_id']}' is an unknown Merchant ID\n";
            continue;
    }
    
    $parser->setOrigin($origin);
	
	//SAMPLE BREAK
	
	foreach ($parser->order_entries as $order_entry)
    {
        if ($origin == 'CYBS_AR' && $parser_type == 'pctools')
        {
            // Check for cancelled orderlines.
            if ($order_entry['status'] == 'CANCELLED')
            {
                continue;
            }
        }
		
	//SAMPLE BREAK
	
		$export = $db->prepare('INSERT INTO ' . CYBS_EXPORT . ' (report_date, report_file, orderdate, orderno, ordertype, product, productcode, company, provider, providercode, channel, channelcode, channelabbr, country, countrycode, regioncode, EUflag, qty, sale, providerfee, channelfee, vatexp, vatIRE, gst, coupon, pmtdate, promotion, promcode, currency, VATID, VATexempt, FirstName, LastName, Street, Street2, City, ZIP, state, ORIGSale, ORIGProvFees, ORIGChannelFee, ORIGVat, ORIGgst, ORIGdiscounts, VATINEURO, VATfxrate, ORIGfxrate, uploaded, transaction_ref_number, order_id, SaleType, entity, customer_id, email, affiliate_type, chargeback_reason_code, card_type, card_expiry, card_bin, card_last_four, ip, chargeback_received_date, report_processed_date, vat_code, vat_rate, ORIGSaleinEUR) VALUES (:report_date, :report_file, :orderdate, :orderno, :ordertype, :product, :productcode, :company, :provider, :providercode, :channel, :channelcode, :channelabbr, :country, :countrycode, :regioncode, :EUflag, :qty, :sale, :providerfee, :channelfee, :vatexp, :vatIRE, :gst, :coupon, :pmtdate, :promotion, :promcode, :currency, :VATID, :VATexempt, :FirstName, :LastName, :Street, :Street2, :City, :ZIP, :state, :ORIGSale, :ORIGProvFees, :ORIGChannelFee, :ORIGVat, :ORIGgst, :ORIGdiscounts, :VATINEURO, :VATfxrate, :ORIGfxrate, :uploaded, :transaction_ref_number, :order_id, :SaleType, :entity, :customer_id, :email, :affiliate_type, :chargeback_reason_code, :card_type, :card_expiry, :card_bin, :card_last_four, :ip, :chargeback_received_date, :report_processed_date, :vat_code, :vat_rate, :ORIGSaleinEUR)');
        $res = $export->execute(array(
            ':report_date'              => $report_date,
            ':report_file'              => $file,
            ':orderdate'                => $parser->order_date . ' ' . $parser->order_time,
            ':orderno'                  => $customer_order_id,
            ':ordertype'                => $order_type,
            ':product'                  => $parser->getProduct($order_entry),
            ':productcode'              => $parser->product_code,
            ':company'                  => $parser->payment_company,
            ':provider'                 => 'CYBS',
            ':providercode'             => '7',
            ':channel'                  => $parser->channel,
            ':channelcode'              => '',
            ':channelabbr'              => '',
            ':country'                  => strtoupper($customer_country['country']),
            ':countrycode'              => $country_code,
            ':regioncode'               => $parser->region_code,
            ':EUflag'                   => $EU_member,
            ':qty'                      => $qty,
            ':sale'                     => '0.00',
            ':providerfee'              => '0.00',
            ':channelfee'               => '0.00',
            ':vatexp'                   => null,
            ':vatIRE'                   => '0.00',
            ':gst'                      => '0.00',
            ':coupon'                   => '0.00',
            ':pmtdate'                  => $parser->order_date,
            ':promotion'                => $parser->getDiscountType($order_entry),
            ':promcode'                 => $parser->getDiscountName($order_entry),
            ':currency'                 => $record_payment_currency,
            ':VATID'                    => $payment_vat,
            ':VATexempt'                => $parser->payment_vat_exempt,
            ':FirstName'                => $parser->payment_FirstName,
            ':LastName'                 => $parser->payment_LastName,
            ':Street'                   => $parser->payment_Street,
            ':Street2'                  => $parser->payment_Street2,
            ':City'                     => $parser->payment_City,
            ':ZIP'                      => $parser->payment_ZIP,
            ':state'                    => $parser->payment_state,
            ':ORIGSale'                 => $ORIGsale,
            ':ORIGProvFees'             => $ORIGProvFees,
            ':ORIGChannelFee'           => '0.00',
            ':ORIGVat'                  => $ORIGVat,
            ':ORIGgst'                  => $ORIGGst,
            ':ORIGdiscounts'            => $discount_value,
            ':VATINEURO'                => $vatInEuro,
            ':VATfxrate'                => $currency_to_EUR_ex_rate,
            ':ORIGfxrate'               => $ofxrate,
            ':uploaded'                 => '0',
            ':transaction_ref_number'   => $data['trans_ref_no'],
            ':order_id'                 => $order_id,
            ':SaleType'                 => $parser->saletype,
            ':entity'                   => $parser->entity,
            ':customer_id'              => $customer_id,
            ':email'                    => $parser->email,
            ':affiliate_type'           => $affiliate,
            ':chargeback_reason_code'   => '',
            ':card_type'                => $parser->card_type,
            ':card_expiry'              => $parser->card_expiry,
            ':card_bin'                 => $parser->card_bin,
            ':card_last_four'           => $parser->card_last_four,
            ':ip'                       => $parser->ip,
            ':chargeback_received_date' => null,
            ':report_processed_date'    => date('Y-m-d'),
            ':vat_code'                 => $vat_code, 
            ':vat_rate'                 => $vat_rate, 
            ':ORIGSaleinEUR'            => $ORIGSaleinEUR
            ));
        if (!$res)
        {
            $res_err = $export->errorInfo();
            echo "FAILED SAVING EXPORT ENTRY FOR ORDER {$data['merchant_ref_number']}\n";
            print_r($res_err);
            echo "\n";
            die("[FAIL] $file failed to finish processing\n");
        }
    }
    
    switch ($order_type)
    {
        case 'S':
            $return = $parser->setPaid();
            break;
        case 'R':
            $return = $parser->setRefunded($partial_refund);
            break;
        default:
            $return = false;
    }
    if ($return)
    {
        echo $return;
    }    
    unset($parser, $order_total);
}

//Check to ensure that each order has the same amount in the export table as in the report file
$sql = 'SELECT order_id, SUM( ABS( ORIGsale + ORIGvat + ORIGgst + ORIGdiscounts )) AS amount
        FROM ' . CYBS_EXPORT . "
        WHERE report_file = '$file'
        GROUP BY order_id";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $row)
{
    if (round($report_amounts[$row['order_id']]['total'],2) != round($row['amount'],2))
    {
		$diff = $row['amount'] - $report_amounts[$row['order_id']]['total'];
		echo "EXPORT AMOUNT DOESN'T MATCH RECORD. EXPORT={$row['amount']} REPORT={$report_amounts[$row['order_id']]['total']} DIFF={$diff} FOR ORDER {$row['order_id']}\n";
        $exception = 'INSERT INTO ' . CYBS_EXCEPTIONS . ' (import_date, report_date, report_file, order_id, export_amount, report_amount, diff, sales, refunds, chargebacks, corrected) VALUES (\'' . date('Y-m-d') . "', '{$report_date}', '{$file}', '{$row['order_id']}', '{$row['amount']}', '{$report_amounts[$row['order_id']]['total']}', '{$diff}', '{$report_amounts[$row['order_id']]['S']}', '{$report_amounts[$row['order_id']]['R']}', '{$report_amounts[$row['order_id']]['C']}', 0);";
        $db->query($exception);
    }
}

//Finished processing so output some stats and move the file to the done directory		
$total_records = $sales + $refunds + $chargebacks;
echo 'Processed ' . $total_records . " Records\n";
echo "Sales: $sales | Refunds: $refunds | Chargebacks: $chargebacks ... ";
if (rename($filename, $directory_done . $file))
{
    echo "[DONE]\n";
}
else 
{
    echo "[ERROR] can't rename $file\n";
}
//EOF
