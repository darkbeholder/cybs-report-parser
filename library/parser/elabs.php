<?php
/**
 * Elabs Store Order Parser
 *
 * @Author Nick Mather
 * @Copyright 2011-2014 Symantec Corporation
 */
namespace Reports\Parser;

class ElabsParser
{
    private $db = '';
    private $order_id = '';
    private $request_id = '';
    private $order_date = '';    
    private $trans_ref_no = '';
    private $origin = '';
    private $payment_id = '';
    private $payment_company = '';
    private $payment_FirstName = '';
    private $payment_LastName = '';
    private $payment_country = '';
    private $payment_Street = '';
    private $payment_Street2 = '';
    private $payment_City = '';
    private $payment_ZIP = '';
    private $payment_state = '';
    private $payment_vat = '';
    private $payment_vat_exempt = '';
    private $channel = '';
    private $customer_id = 0;
    private $email = '';
    private $card_type = '';
    private $card_expiry = '';
    private $ip = '';
    private $order_entries = array();
    private $customer_order_id = 0;
    private $card_bin = 0;
    private $card_last_four = 0;
    private $order_time = '00:00:00';
    private $entity = '';
    private $region_code = '';
    private $order_total = 0;
    
    public function __construct($db, array $data) {
        $this->db = $db;
        $this->order_id = $data['merchant_ref_number'];
        $this->request_id = $data['request_id'];
        $this->order_date = $data['batch_date'];
        $this->trans_ref_no = $data['trans_ref_no'];
    }
    
    public function __get($param)
    {
        return isset($this->$param) ? $this->$param : false;
    }
    
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }
    
    public function getSaleType($order_entry)
    {
        $sql = 'SELECT *
                FROM ' . ELABS_SKU . " 
                WHERE id = {$order_entry['sku_id']}";
        $sku = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        $sku_type = strtoupper($sku['type']);
        
        if ($this->origin == 'CYBS_AR')
        {
            $this->product_code = 'AR';
            $this->saletype = 3;
        }
        else if ($sku_type == 'RENEW')
        {
            $this->product_code = 'MR';
            $this->saletype = 4;
        }
        else if ($sku_type == 'UPGRADE' || $sku_type == 'PLUGIN' || $sku_type == 'MIDCYCLE_UPGRADE' || $sku_type == 'RENEWAL_PLUGIN')
        {
            $this->product_code = 'MU';
            $this->saletype = 5;
        }
        else if ($sku_type == 'subscription')
        {
            $this->saletype = 6;
        }
        else
        {
            $this->saletype = 2;
        }
    }
    
    public function getCustomerId()
    {
        //Allows NULL to be returned
        return $this->customer_id;
    }
    
    public function getProductTypeId($order_entry)
    {
        return $order_entry['sku_id'];
    }
    
    public function setLicenseChargeback($order_entry)
    {
        //Elabs doesn't maintain license status in it's local database
		return false;
    }
        
    public function getSkippedSkus()
    {
        return array(16, 20);
    }    
//END OF SAMPLE
}
