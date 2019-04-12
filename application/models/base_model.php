<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class base_model extends CI_Model {

    public function getEmployee($title) {
    	$this->db->select('employeeNumber,CONCAT(firstName," ", lastName) AS name,jobTitle');
        return $this->db->get_where('employees', ['jobTitle'=>$title])->result_array();
    }

    public function getOffices() {
    	$this->db->select('officeCode,City');
        return $this->db->get_where('offices')->result_array();
    }

    public function getEmployeeUnder($employeeNumber) {
        return $this->db->select('employeeNumber,CONCAT(firstName," ", lastName) AS name,jobTitle')->get_where('employees', ['reportsTo'=>$employeeNumber])->result_array();
    }

    public function getOfficeEmployees($officeCode) {
    	$this->db->select('employeeNumber,CONCAT(firstName," ", lastName) AS name,jobTitle');
        return $this->db->get_where('employees', ['officeCode'=>$officeCode])->result_array();
    }

    public function getEmployeeDetails($employeeNumber=null) {
    	$this->db->select(
    		'employees.employeeNumber,
    		CONCAT(employees.firstName," ", employees.lastName) AS name,
    		employees.jobTitle,
    		offices.officeCode,
    		offices.city'
    	);
        $this->db->from('employees');
        $this->db->join('offices', 'offices.officeCode = employees.officeCode');
        if(!empty($employeeNumber)){
        	$this->db->where('employees.employeeNumber',$employeeNumber);
        }
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getCustomersOrdersByEmployeeNumber($employeeNumber){
    	$sql = <<<EOD
    	SELECT 
		  	orderdetails.orderNumber,
		  	orderdetails.productCode,
		  	orderdetails.quantityOrdered,
		  	orderdetails.priceEach,
		  	(orderdetails.priceEach * orderdetails.quantityOrdered) AS sale,
		  	orders.status,
		  	products.productName,
		 	 products.productLine,
		  	products.productScale,
		  	products.quantityInStock,
  			productlines.textDescription
		FROM
		  `orderdetails` 
		  LEFT JOIN `orders` 
		    ON (
		      orders.orderNumber = orderdetails.orderNumber
		    ) 
		  LEFT JOIN `customers` 
		    ON (
		      orders.customerNumber = customers.customerNumber
		    ) 
		  LEFT JOIN `products` 
		    ON (
		      `orderdetails`.productCode = products.productCode
		    ) 
		  LEFT JOIN `productlines` 
		    ON (
		      `productlines`.productLine = products.productLine
		    ) 
		WHERE customers.salesRepEmployeeNumber = ?
		  AND `status` = "Shipped" 
EOD;
		$query = $this->db->query($sql, array(
			$employeeNumber,
		));
		return $rows=$query->result_array();
    }
}
