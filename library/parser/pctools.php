<?php
/**
 * PC Tools Shop Order Parser
 *
 * @Author Nick Mather
 * @Copyright 2011-2014 Symantec Corporation
 */

namespace Reports\Parser;

class PctoolsParser
{
    private $db = '';
    private $order_id = '';
    private $request_id = '';
    private $order_date = '';    
    private $trans_ref_no = '';
    private $origin = '';
    private $cs_order_id = '';
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
    private $saletype = '';
    private $product_code = '';
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
        if ($this->origin == 'CYBS_AR')
        {
            $this->product_code = 'AR';
            $this->saletype = 3;
        }
        else if ($order_entry['purchase_type'] == 'RENEWAL')
        {
            $this->product_code = 'MR';
            $this->saletype = 4;
        }
        else if ($order_entry['purchase_type'] == 'UPGRADE' || $order_entry['purchase_type'] == 'PLUGIN' || $order_entry['purchase_type'] == 'MIDCYCLE_UPGRADE' || $order_entry['purchase_type'] == 'RENEWAL_PLUGIN')
        {
            $this->product_code = 'MU';
            $this->saletype = 5;
        }
        else
        {
            $sql = 'SELECT subscription 
                    FROM ' . PRODUCT_TYPES . ' 
                    WHERE product_type_id = ' . $order_entry['product_type_id'];
            $result = $this->db->query($sql)->fetchColumn();
            if ($result == 'Y')
            {
                $this->saletype = 6;
            }
            else
            {
                $this->saletype = 2;
            }   
        }
    }
    
    public function getCustomerId()
    {
        //Allows NULL to be returned
        return $this->customer_id;
    }
    
    public function getProductTypeId($order_entry)
    {
        return $order_entry['product_type_id'];
    }
    
    public function setLicenseChargeback($order_entry)
    {
        if (empty($order_entry['license_code']))
        {
            //No license code. Probably a CD so skip
            return false;
        }
        $sql = 'SELECT *
                FROM ' . CUSTOMER_LICENSES . "
                WHERE license_code = '{$order_entry['license_code']}'";
        $license = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($license === false)
        {
            $return = "FAILED LOADING LICENSE CODE {$order_entry['license_code']} FOR ORDER " . $this->order_id . "\n";
            return $return;
        }
        if ($license['status'] != 6) // No point trying to set the status to 6 when it is already set to 6
        {
            $today = date('Y-m-d');
            $this->db->query('UPDATE ' . CUSTOMER_LICENSES . "
                        SET status = 6, 
                            expiry = '{$today}'
                        WHERE license_code = '{$order_entry['license_code']}'");
        }
        return false;
    }
    
    public function getSkippedSkus()
    {
        return array();
    }
//END OF SAMPLE
}
