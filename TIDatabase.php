<?php


class TIDEDatabase {

    protected $connection;     
	protected $server;
    protected $user; 		
    protected $password; 		
    protected $database;   					
	protected $persist_connection;		
	protected $database_type;	
					
	public $query_id;
	public $error 			= "";						
	public $errno 			= 0;					
	public $affected_rows 	= 0;				
	public $debbug 			= false;					

	/**
	 * __construct() - TIDEDatabase Class constructor  **
	 * 
	 * @param string $server
	 * @param string $user
	 * @param string $password
	 * @param string $persist_connection
	 * @throws Exception 
	 */
	public function __construct($server, $user, $password, $persist_connection=false){
		
		if(!$server){
			throw new Exception("Server is not defined.");
		}
		if(!$user){
			throw new Exception("Database user is not defined");
		}
		if(!$password){
			throw new Exception("Database password is not defined");
		}
		
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->persist_connection = $persist_connection;
		
		return $this->server_connection();
	}
	
	/**
	 * server_connection() - Establish connection to Mysql server	**
	 * 
	 * @throws Exception
	 */
	public function server_connection(){
		
		if(!$this->persist_connection){
				
			$this->connection = @mysql_connect($this->server, $this->user, $this->password);
				
		
		}else{
				
			$this->connection = @mysql_pconnect($this->server, $this->user, $this->password);
				
		}
		
		if (!$this->connection) {
		
			throw new Exception("Could not connect to server &nbsp;: &nbsp " . $this->server . "");
			exit();
		}
		
		if (!@mysql_ping($this->connection)) {
		
			throw new Exception("Lost connection to Server &nbsp;: &nbsp " . $this->server . "");
			exit();
		}
		
	}
	
	/**
	 * set_db() - Set database
	 * 
	 * @param string $database		**
	 */
	public function set_db($database){
		
		if($database && $this->database !== $database){
				
			$this->database = $database;
		}
			
			
		return mysql_select_db($this->database, $this->connection);
	}
	
	/**
	 * close_db_connection() - Close database connection
	 * 
	 * @throws Exception
	 */
	public function close_db_connection(){
	
		if(!$this->persist_connection){
	
			if(!@mysql_close($this->connection)){
					
				throw new Exception("Connection close failed &nbsp;: &nbsp " . $this->server . "");
					
			}
	
		}else{
	
			throw new Exception("Persistant connection can't be closed  &nbsp;: &nbsp " . $this->server . " ");
		}
	}
	
	/**
	 * escape_data()
	 * 
	 * @param unknown_type $data
	 */
	public function escape_data($data) {
	
		if(get_magic_quotes_runtime()){
	
			$data = stripslashes($data);
	
			return mysql_real_escape_string($data);
	
		}
	
	}
	
	/**
	 * sql_query()
	 * 
	 * @param string $query
	 * @param string $operation
	 * @throws Exception
	 */
	public function sql_query($query, $operation) {
	
		$this->query_id = @mysql_query($query , $this->connection);
	
		if (!$this->query_id) {
	
			throw new Exception("Mysql query failed &nbsp;: &nbsp; database name - (".$this->database."), &nbsp;&nbsp; operation - (" . $operation . ")");
			exit;
				
		}
		 
		if(preg_match('/SELECT|SHOW/', $operation)){
	
			$this->affected_rows = @mysql_num_rows($this->query_id);
	
	
		}else{
	
			$this->affected_rows = @mysql_affected_rows($this->query_id);
	
		}
	

		return $this->query_id;
		
	}
	
	/**
	 * check_db_existance() - Checking if Database exists		**
	 * 
	 * @param bool|string $database
	 * @param bool $response
	 * @throws Exception
	 */
	public function check_db_existance($database=false, $response=true){
		
		$db_selected = $this->set_db($database);
		
		if (!$db_selected) {
			
			$result = false;
			
		}else{
			
			$result = true;
			
		}
		
		if($result){
			
			if($response){
				
				return $response;
			}
			
		}else{
			
			throw new Exception("Database ". $this->database ." do not exists on server " . $this->server ."");
			exit();
		}
	
	
	}
	
	/**
	 *  free_result()
	 * 
	 * SELECT, SHOW, EXPLAIN, and DESCRIBE queries
	 */

	public function free_result(){
		
		if($this->query_id !== 0 && !@mysql_free_result($this->query_id)) {
	
			$this->errorMessage("Not possible to free query id ".$this->query_id ."");
			exit;
				
		}
	}
	/**
	 * create_db() - Database creation
	 * 
	 * @param string $database
	 * @param string $settings
	 * @param bool $drop
	 * @param bool $response
	 */
	
	public function create_db($database, $settings=false, $drop=true, $response=false) {
		
		$this->set_db($database);
		
		if(!$settings){
	
			$settings = 'DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci';
	
		}
		
		if($drop){
	
			$this->drop_db();
	
		}
	
		$query = "CREATE DATABASE IF NOT EXISTS " . $database . " " . $settings . "";
	
		$result = $this->sql_query($query, 'CREATE DATABASE');
		
		if($response){
				
			return $result;
				
		}
		
	}
	
	/**
	 * drop_db()
	 * 
	 * @param string $database
	 * @param bool $response
	 */
	public function drop_db($database, $response=true) { 
		
		$this->set_db($database);
		
		$query = "DROP DATABASE IF EXISTS ". $database ."";
	
		$result = $this->sql_query($query, 'DROP DATABASE');
		
		if($response){
		
			return $result;
		
		}
	}
	
	/**
	 * create_table() - Create database table/s
	 *
	 * @param bool|string $database
	 * @param string|array $table
	 * @param bool $drop
	 */
	public function create_table($database=false, $table, $table_fields, $response=true, $drop=false) {
	
		$this->set_db($database);
	
		if(is_array($table)){
				
			$result = array();
			
			$query = array();
			
			foreach($table as $key=>$table_name){
					
				if($drop){
	
					$this->drop_db($table_name, 'TABLE');
				}
	
				$query_table = "CREATE TABLE ". $table_name ." (" . $table_fields[$key] . ")";
				$result[] = $this->sql_query($query_1, 'CREATE TABLE');

			}
				
				
		}else{
			
			if($drop){
			
				$this->drop_db($table, 'TABLE');
			}
			
			$query_table = "CREATE TABLE ". $table ." (" . $table_fields. ")";
			$result[] = $this->sql_query($query_1, 'CREATE TABLE');
	
		}
		
		if($response){
		
			return $result;
		
		}
		
	}
	
	/**
	 * insert_table_data() - Insert data into table/s
	 * 
	 * @param string $database
	 * @param string|array $table
	 * @param string|array $table_fields
	 * @param string|array $table_data
	 * @param string|array $statement
	 * @param bool $response
	 * @param bool $empty
	 */
	public function insert_table_data($database=false, $table, $table_fields, $table_values, $statement, $response=true, $empty=false){
		
		$this->set_db($database);
		
		if(is_array($table)){
		
			$result = array();
				
			$query = array();
				
			foreach($table as $key=>$table_name){
					
				if($empty){
		
					$this->empty_table($table_name);
				}
		
		
				$query = "INSERT INTO (". $table_name .") (" . $table_fields[$key] . ") VALUES (". $table_values[$key] .") " . $statement[$key] . "";

				$result[]  = $this->sql_query($query, 'INSERT TABLE DATA');
			}
		
		
		}else{
				
			if($empty){
					
				$this->empty_table($table);
			}
			
			$query = "INSERT INTO (". $table .") (" . $table_fields . ") VALUES (". $table_values .") " . $statement . "";
				
			$result[] = $this->sql_query($query, 'INSERT TABLE DATA');
		
		}
		
		if($response){
		
			return $result;
		
		}
		
	}
	
	/**
	 * drop_table()
	 * 
	 * @param string|bool $database
	 * @param string|array $table
	 */
	public function drop_table($database=false, $table, $response=true) {
		
		$this->set_db($database);

		if(is_array($table)){
		
			$result = array();
		
			$query = array();
		
			foreach($table as $key=>$table_name){
				
				$query = "DROP TABLE IF EXISTS " . $table_name . "";
		
				$result[]  = $this->sql_query($query, 'DROP TABLES');
			}
		
		
		}else{
		
			$query = "DROP TABLE IF EXISTS " . $table . "";
			$result = $this->sql_query($query, 'INSERT TABLE DATA');
		
		}
		
		if($response){
		
			return $result;
		
		}

	}
	
	/**
	 * show_tables() - Show database tables and rows.
	 * 
	 * @param string|bool $database
	 * @param bool $show_columns
	 */
	public function show_tables($database=false, $show_columns=true){
	
		$this->set_db($database);
		
		$query = "SHOW TABLES FROM ".$this->database."";
	
		$result = $this->sql_query($query, 'LISTING TABLES');
	
		$data = $this->fetch_data($result, 'row');
			
		if($show_columns){
			
			$list = $this->show_columns(false, $data);

		}
	
		return $list;
	
	}
	
	/**
	 * show_columns() - Show tables columns
	 * 
	 * @param string|bool $database
	 * @param unknown_type $table
	 */
	public function show_columns($database=false, $table){
		
		$this->set_db($database);
		
		if(is_array($table)){
		
			$query = array();
			
			foreach($table as $key => $table_name){
			
				$query = "SHOW COLUMNS FROM " . $table_name . "";
			
				$result  = $this->sql_query($query, 'LISTING TABLE FIELDS');
				
				$data[$table_name] = $this->fetch_data($result, 'assoc');
			}
			
			
		}else{
			
			$query = "SHOW COLUMNS FROM ".$table[0]."";
				
			$result  = $this->sql_query($query, 'TABLE FIELDS');
			
			$data = $this->fetch_data($result, 'assoc');
			
		}
		
		return $data;

	}
	
	/**
	 * fetch_data() - Mysql fetch implementation
	 * 
	 * @param string $data
	 * @param string $fetch_type
	 * @throws Exception
	 */
	public function fetch_data($data, $fetch_type = 'assoc', $extra_data=false){
	
		$records = array();
		$temp_records ;
		
		switch($fetch_type){
			case 'array':
				while($row = mysql_fetch_array($data, MYSQL_BOTH)){
					$records[]= $row;
				}
				break;
			case 'assoc':
				while($row = mysql_fetch_assoc($data)){
					$records[]= $row;
				}
				break;			
			case 'row':
				while($row = mysql_fetch_row($data)){
					$temp_records = $row;
					$records[] = $temp_records[0];
				}
				break;			
			case 'object':
				while($row = mysql_fetch_object($data)){
					$records[]= $row;
				}
				break;		
			case 'field':
				while($row = mysql_fetch_field($data)){
					$records[]= $row;
				}
				break;
			case 'lengths':
				while($row = mysql_fetch_lengths($data)){
					$records[]= $row;
				}
				break;
			case 'spec_row':	
				$records = mysql_result($data,  $extra_data);
				break;				
				
			default:
				throw new Exception("Fetch type undefined");
				exit();
		}
	
		return $records;
	}
	
	/**
	 * replace_prefix() - Change Database Table Prefix
	 * 
	 * @param string $database
	 * @param string $new_prefix
	 * @param string $old_prefix
	 * @param bool $response
	 * @throws Exception
	 */
	public function replace_prefix($database=false, $new_prefix=false, $old_prefix=false, $response=true){
		
		$this->set_db($database);

		if(!$new_prefix){
			
			throw new Exception("You must define new table prefix");
			exit();
		
		}
		
		$tables = show_tables();
		
		if(!$old_prefix){
			
			$find_prefix = strpos($tables_list[0], '_');
			
			if($find_prefix){
				
				$old_prefix = $find_prefix;
				
			}
				
		}
		
		foreach($tables as $key=>$table_name){
	
			if(!$old_prefix){
					
				$new_table_name = $new_prefix . $table_name;
					
			}else{
	
				$new_table_name = str_replace($old_prefix, $new_prefix ,  $table_name);
					
			}
				
			$query =  'RENAME TABLE ' . $table_name . ' TO '. $new_table_name . '';
				
			$result = $this->sql_query($query, 'CHANGING TABLE PREFIX');
				
		}
		
		$this->close_db_connection();
		
		if($response){
		
			return $result;
		
		}
		
	
	}

	/**
	 * rename_tables()
	 * 
	 * @param bool|string $database
	 * @param string|array $table
	 * @param string|array $new_name
	 * @param bool $response
	 */
	public function rename_tables($database=false, $table, $new_name, $response=true){
	
		$this->set_db($database);
		
		if(is_array($table)){
			
			$tables_list = show_tables();
		
			$check_tables = array_intersect($table, $tables_list);
			
			if (count($check_tables) !== count($check_tables) ){
					
				throw new Exception("Some of tables do not exists in database.");
				exit();
					
			}
			
			foreach($table as $key => $table_name){
					
					
				$query =  'RENAME TABLE ' . $table_name . ' TO '. $new_name[$key] .'';
			
				$result = $this->sql_query($query, 'RENAMING TABLE');
				
			}
			
			if (in_array(false, $result)) {
				
				$result = false;
				
			}
				
		}else{
			
			$query =  'RENAME TABLE ' . $table . ' TO '. $new_name .'';
				
			$result = $this->sql_query($query, 'RENAMING TABLE');
		}
		
		if($response){
		
			return $result;
		
		}
	}
	
	/**
	 * select_data()
	 * 
	 * @param bool|string $database
	 * @param string|array $table
	 * @param string|array $table_fields
	 * @param string|array $db_query
	 * @param string|array $fetch_type
	 * @param string|array $row_number
	 */
	public function select_data($database=false, $table, $table_fields, $db_query, $fetch_type='assoc', $row_number=false) {
	
		$this->set_db($database);
		
		if(is_array($table)){
			
			$result = array();
			
			foreach($table as $key => $table_name){
				
				$query = "SELECT " . $table_fields[$key] . " FROM " . $table_name . " " . $db_query[$key] . "";
				
				$result[$key] = $this->sql_query($query, 'SELECT DATA');
				
				if(is_array($fetch_type)){
					
					$fetch = $fetch_type[$key];
					
				}else{
					
					$fetch = $fetch_type;
					
				}
				
				if($fetch == 'spec_row'){
						
					$data[$key] = $this->fetch_data($result[$key], 'spec_row', $row_number[$key]);
				
				}else{
						
					$data[$key] = $this->fetch_data($result[$key], $fetch);
				
				}
			}
		}else{
			
			$query = "SELECT " . $table_fields . " FROM " . $table . " " . $db_query . "";
	
			$result = $this->sql_query($query, 'SELECT DATA');
	
			if($fetchType == 'spec_row'){
				
				$data = $this->fetch_data($result, 'spec_row', $row_number);
					
			}else{
				
				$data = $this->fetch_data($result, $fetch_type);
					
			}
		}
		
		return  $data;
	
		$this->free_result();
	
		$this->close_db_connection();
	
	}
	
	public function rename_db($old_db_name, $new_db_name){
		
// 		$tables = array();
		
// 		$tables = $this->showTables_Fields($old_db);
		
// 	   	$this->createDatabase($new_db, $data='', $dropOld = false);
		
		
// 		$query_1 = "CREATE TABLE ".$tableValues[0]." (".$tableValues[1].")";
		
// 		$result_1 = $this->sql_query($query_1, 'CREATE TABLE');
	
// 	RENAME TABLE db_name.table1 TO new_db_name,
// 	                     db_name.table2 TO new_db_name;
// 	DROP database db_name;
	
	
// 		$this->createTables($tables, $new_db);
// 	# $tableData - **tableData[0] -table_name  **tableData[1] - create fields code  **tableData[2] - insert data **tableData[3] drop/skip
	
// 	}
	
	}
	





//WE SET EVERY REQUEST AS STRING NOT ARRAY AND WHEN WE NEED ARRAY JUST EXPLODE AND COMBINE FIELD AND VALUES DATA
public function dataToArray($dataFields, $dataValues){
	
	$arrayDataFields = explode(',', $dataFields);
	$fieldsNum = count($arrayDataFields);
	if(!empty($dataValues)){
	$arrayDataValues = explode(',', $dataValues);
	$valuesNum = count($arrayDataValues);
	
	if($fieldsNum !== $valuesNum){
		
		throw new Exception("Number of fields and values must match : FIELDS->".fieldsNum.", VALUES->".valuesNum."");
			exit();
										 
	}
	return array_combine($arrayDataFields, $arrayDataValues);
	
	}else{
		
	return $arrayDataFields;
	
	}
}
















/**
# addSlashes - add slashes when we need eg. array_walk($values, array($this, "addSlashes"));
*/

public function addSlashes(&$value){
return $value = "'$value'";
} 

public function addDBSlashes(&$value){
return $value = "`$value`";
} 

/**
# insertData - insert data array to table
*/

	public  function insertRecords($table, $field, $value, $statement='') {

		$query = "INSERT INTO $table ";

		$fields = "";
		$vals = "";

		if(is_array($field) && is_array($value)){
				
			if(count($field) !== count($value)){

				throw new Exception("Number of filelds and update values do not match.");

			}
				
			$last = end($value);
				
			for($i=0;$i<count($fields);$i++){

				if($fields[$i] == $last ){

					$fields .= "".$fields[$i]." ";
					$vals .= "'".$value[$i]."' ";

				}else{
					$fields .= "".$fields[$i].", ";
					$vals .= "'".$value[$i]."', ";
						
				}

			}
			//			$query .= "($fields) VALUES ($vals)";
			//			$query .= " WHERE $database_row = $reference_row";

		}elseif(empty($field) && is_array($value)){
				
			$last = end(array_keys($value));
				
			foreach($value as $key=>$val){
					
				if($key == $last){
					$fields .= "".$key." ";
					$vals .= "'".$val."' ";
						
				}else{
						
					$fields .= "".$key.", ";
					$vals .= "'".$val."', ";
						
				}
					
			}

		}else{
				
			$fields .= $field;
			$vals .=  $value;
				
		}

		$query .= "($fields) VALUES ($vals) {$statement}";
		//$query .= " WHERE $database_row = $reference_row";

		//echo $query;
		file_put_contents('log.txt', $query);
		$this->sql_query($query, 'INSERT DATA');
			
	}

/**
# deleteData - delete data array to table
*/
public  function deleteData($dataDelete, $database='') {
	
if(!empty($database)){
		
		 $this->dbname = $database;
		 mysql_select_db($this->dbname, $this->connection);
		 
	}
$tablesNamesList = $this->showTables_Fields($this->dbname);

	foreach($dataDelete as $table=>$data){

		if(in_array($data[0], $tablesNamesList)){
			
			$query = "DELETE  FROM ".$data[0]."  ".$data[1]." ";
			$this->sql_query($query, 'DELETE DATA');
		}
	}

}  

	public  function deleteRecords($table, $ref, $value) {

		//check if user exist in database
		if(!$this->selectData("*", $table, " WHERE {$ref} = '{$value}'")){

			throw new Exception("Data do not exist.");

		}else{
				
			// if user do not exist trow exception exist else delete it from database


			$query = "DELETE  FROM ". $table ." WHERE ". $ref ." = '". $value ."'";
				
			$this->sql_query($query, 'DELETE DATA');

		}
	}


/**
# updateData - update data array to table
*/
public  function updateData($updateData, $database='') {
if(!empty($database)){
		
		 $this->dbname = $database;
		 mysql_select_db($this->dbname, $this->connection);
		 
	}
	
	$tablesNamesList = $this->showTables_Fields($this->dbname);

	
	foreach($updateData as $table=>$data){
		
		if(in_array($data[0], $tablesNamesList)){

				$query = "UPDATE ".$data[0]."  ".$data[1]." ".$data[2]."";
				$this->sql_query($query, 'UPDATE DATA');
				
				if ($this->affected_rows == 0) {
					
					$this->errorMessage("Table ".$data[0]." - update failed");
				}

		}
	}
}
	public  function updateRecords($table, $field, $value, $database_row, $reference_row) {




		if(is_array($field) && is_array($value)){
				
			$query = "UPDATE $table SET ";
				
			if(count($field) !== count($value)){

				throw new Exception("Number of filelds and update values do not match.");

			}
				
			$last = end($value);
			for($i=0;$i<count($field);$i++){

				if($i == (count($value)-1 )){

					$query .= $field[$i] ." = '".$value[$i]."' ";

				}else{
						
					$query .= $field[$i] ." = '".$value[$i]."',";

				}

			}

			$query .= " WHERE $database_row = $reference_row";

		}elseif(empty($field) && is_array($value) && !is_array($reference_row)){
				
			$query = "UPDATE $table SET ";
				
			$last_val  = array_keys($value);
			$last = end($last_val);
			foreach($value as $key=>$val){
					
				if($key == $last){

					$query .= $key ." = '".$val."' ";
						
				}else{
						
					$query .= $key ." = '".$val."', ";
						
				}
					
			}

			 $query .= " WHERE $database_row = '$reference_row'";

		}elseif(is_array($reference_row)){
				
			$query = array();
				
			for ($i = 0; $i < count($reference_row); $i++) {


				$query[$i] = "UPDATE $table SET  {$field} = {$value[$i]} WHERE {$database_row} = '{$reference_row[$i]}'";

			}
				
				
			return $query ;
				
		}else{
				
			$query = "UPDATE $table SET ";
			$query .= "$field = '$value' WHERE $database_row = $reference_row";

				
		}
	//	echo $query;
		$this->sql_query($query, 'UPDATE DATA');
		
		//return $query;
		//		if(!is_array($query)){
		//			$this->sql_query($query, 'UPDATE DATA');
		//		}else{
		//			print_r($query);
		//			for ($i = 0; $i < count($query); $i++) {
		//				$this->sql_query($query[$i], 'UPDATE DATA');
		//			}
		//
		//		}


	}

public  function alterData($tableData,  $database='') {
if(!empty($database)){
		
		 $this->dbname = $database;
		 mysql_select_db($this->dbname, $this->connection);
		 
	}

	$tablesNamesList = $this->showTables_Fields($this->dbname);
	
		foreach($tableData as $table=>$data){
			
			if(in_array($data[0], $tablesNamesList)){

				$query = "ALTER TABLE ".$data[0]." ".$data[1]."";
					$this->sql_query($query, 'ALTER DATA');

				
			}
		}
   }

public  function queryFile($externalFile, $localCopy=false, $database='') {
if(!empty($database)){
		
		 $this->dbname = $database;
		 mysql_select_db($this->dbname, $this->connection);
		 
	}


$match = preg_match("/http|https|ftp/", $externalFile);

if($match == 0){
	
		$fileExist = @file_exists($externalFile);
		
		if (!is_readable($externalFile)) {
			
				chmod($externalFile, 0444);

		} 

		if($fileExist){
			
				$externalFileContent = file_get_contents($externalFile);
				
		}

}else{

		if(extension_loaded('curl')){
					
		$curl_init = curl_init();
		
		curl_setopt($curl_init, CURLOPT_URL, $externalFile);
		curl_setopt($curl_init, CURLOPT_POST, true);
        curl_setopt($curl_init, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_init, CURLOPT_CONNECTTIMEOUT, 30);
		
        curl_exec($curl_init);
		$curl_error = curl_error($curl_init);
        curl_close($curl_init);
	

		if(empty($curl_error)){
			
				$externalFileContent =  $fileExist;
		}else{
			
				$fileExist== false;	
			
		}



//		if(!preg_match("/404 Not Found/", $fileExist)){
//			
//				$externalFileContent =  $fileExist;
//		}else{
//			
//				$fileExist== false;	
//			
//		}
		
		}else{
			
			$fileExist = @fopen($externalFile, 'r');
			
			if($fileExist){
			
			$externalFileContent = stream_get_contents($fileExist);

			
			}
			
		}
		 
}
	if(!$fileExist){
		
		$this->errorMessage("Included file : (".$externalFile.") - do not exist or incorrect path inserted");
		exit;
	
	}
	if($externalFileContent == ''){
		
		$this->errorMessage("Included file : (".$externalFile.") - is empty");
		exit;
	
	}			
//WE NEED LOCAL COPY OF FILE
		if($localCopy){
			
			$databaseFile = dirname(__FILE__).'/cache/data.sql';
			
			
			$checkDir = is_dir(dirname(__FILE__).'/cache/');
			$checkFile = is_file($databaseFile );
			
			if(!$checkDir){
			mkdir(dirname(__FILE__)."/cache/",  0644);
			
			}
			
			if(!$checkFile){
			$wFile = fopen($databaseFile,'w');
						
			file_put_contents($databaseFile, $externalFileContent);
			
			fclose($wFile); 
			}
		}
		 $externalFileContent = explode(';', $externalFileContent);
				
				 array_pop($externalFileContent);
					
				  foreach ($externalFileContent as $query) {
					  
					
					$result = $this->sql_query($query, 'EXTERNAL FILE');
					
				
						if (!$result) {
							$this->errorMessage("PROBLEM DURING EXTERNAL FILE EXECUTING");
						}
					 
				  }


}
	
	/**
	 * backup_db_table() - Backup database tables
	 * 
	 * @param string $database
	 * @param string|array $table
	 * @param bool|string|array $file_path
	 * @param bool|string|array $file_name
	 * @param bool $response
	 */
	public function backup_db_table($database=false, $table, $file_path, $file_name=false, $response=false) {
		
		$db_selected = set_db($database);
			
		if(is_array($table)){
			
			$result = array();
			
			foreach($table as $key => $table_name){
				
				if(!$file_name[$key]){
						
					$file_name = $table_name;
						
				}
				
				$query  = "SELECT * INTO OUTFILE '" . $file_path . '/' .$file_name . ".sql ' FROM ". $table_name ."";
				
				$result[$table_name] = $this->sql_query($query, 'BACKUP DATA');
				
			}
			
			if($response){
			
				return $result;
			
			}
		}else{
			
			if(!$file_name){
			
				$file_name = $table;
			
			}
				
			$query  = "SELECT * INTO OUTFILE '" . $file_path . '/' .$file_name . ".sql ' FROM ". $table ."";
				
			$result = $this->sql_query($query, 'BACKUP DATA');
				
			if($response){
			
				return $result;
			
			}
		}
		
	}
	 
	/**
	 * 
	 * @param bool|string $database
	 * @param string|array $table
	 * @param string $backup_path
	 * @param string|bool|array $backup_name
	 * @param bool $response
	 */
	public function restore_db_table($database=false, $table, $backup_path, $backup_name=false, $response=false) {
		
		$db_selected = set_db($database);
		
		if(is_array($table)){
			
			$result = array();
			
			foreach($table as $key => $table_name){
				
				if(!$backup_name[$key]){
				
					$backup_name = $table_name;
				
				}
								
				$query   = "LOAD DATA INFILE '" . $backup_path . '/' .$backup_name . ".sql ' INTO TABLE ". $table_name ."";
				
				$result[$table_name] = $this->sql_query($query, 'RESTORE DATA');
				
				}
					
				if($response){
						
					return $result;
						
				}
		}else{
			
			if(!$backup_name){
					
				$backup_name = $table;
					
			}
			
			$query   = "LOAD DATA INFILE '" . $backup_path . '/' .$backup_name . ".sql ' INTO TABLE ". $table ."";
			
			$result = $this->sql_query($query, 'BACKUP DATA');
			
			if($response){
					
				return $result;
					
			}
			
		}

	}

public function showData($queryString,  $database='') {
if(!empty($database)){
		
		 $this->dbname = $database;
		 mysql_select_db($this->dbname, $this->connection);
		 
	}
$query   = "".$queryString."";
$data = $this->sql_query($query, 'QUERY DATA');

return $this->fetch_data($data, $fetchType = 'assoc');


}

/*
# backupDatabase - backup complete database
#params : 
*/
public function backupDatabase($backupPath='', $compression='false', $database='') {
if(!empty($database)){
		
		 $this->dbname = $database;
		 
	}

$serverInfo = $this->serverInfo();

 
$databaseData = $this->showTables_Fields($this->dbname, true);

$result_ENGINE = $this->selectData("ENGINE", 'information_schema.TABLES', "WHERE TABLE_SCHEMA = '".$this->dbname."'");

        $query_1 = "show variables like 'character_set_database'";
		
        $query_2 = "show variables like 'collation_database'";
		
        $query_3 = "SHOW TABLE STATUS";
		

$charset = $this->showData($query_1);
$collation = $this->showData($query_2);
$table_status =  $this->showData($query_3);

$hostName = preg_replace('/MySQL host info: /',  '', $serverInfo['host_info']);
$serverVersion = preg_replace('/[a-z -]/us',  '', $serverInfo['server_info']);



	  $exportData  = '-----------------------------------------------------------'."\n";
      $exportData .= '--     teDatabase SQL Dump    -----------------------------'."\n";
      $exportData .= '--     http://te-edu.com    -------------------------------'."\n";
	  $exportData .= '--     te-edu development team    -------------------------'."\n";
	  $exportData .= '-----------------------------------------------------------'."\n";
	  $exportData .= '--     FILE CREATED WITH  : -------------------------------'."\n";
	  $exportData .= '--     teDatabase class version 1.11   --------------------'."\n";
	  $exportData .= '-----------------------------------------------------------'."\n";
 	  $exportData .= '--     HOST NAME : ----------------------------------------'."\n";
	  $exportData .= '--     '.$hostName.'                                  '."\n";
	  $exportData .= '-----------------------------------------------------------'."\n";
 	  $exportData .= '--     FILE CREATION TIME : -------------------------------'."\n";
	  $exportData .= '--     '.date('l dS \of F Y h:i:s A').'               '."\n";
	  $exportData .= '-----------------------------------------------------------'."\n";
 	  $exportData .= '--     SERVER VERSION :  ----------------------------------'."\n";
	  $exportData .= '--     '.$serverVersion.'                             '."\n";
	  $exportData .= '-----------------------------------------------------------'."\n";
 	  $exportData .= '--     PHP VERSION :     ----------------------------------'."\n";
	  $exportData .= '--     '. phpversion().'                              '."\n";
	  $exportData .= '-----------------------------------------------------------'."\n\n";
	  
	  $exportData .= 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO                   '."\n\n";
	  
	  $exportData .= '-----------------------------------------------------------'."\n";
	  $exportData .= '--     DATABASE NAME : '.$this->dbname.'              '."\n";
	  $exportData .= '-----------------------------------------------------------'."\n\n";

	  $exportData .= 'CREATE DATABASE `'.$this->dbname.'` DEFAULT CHARACTER SET '.$charset[0]['Value'].' COLLATE '.$collation[0]['Value'].';'."\n\n";

$counter = 0;
$increment = '';
foreach($databaseData as $table=>$tableFiels){

	$tableKeysData = '';		
$query_4 = "SHOW KEYS FROM ".$table."";
$tableKeys =  $this->showData($query_4);

$type = array();
for($i=0;$i<count($tableKeys);$i++){
	
	$type[$tableKeys[$i]["Key_name"]][] = array($tableKeys[$i]["Column_name"], $tableKeys[$i]["Non_unique"]);

}

foreach($type as $key=>$keyType){

			if(end($type) !== $keyType){
				$comma = ', ';
			}else{
				$comma = '';
			}
	if($key == 'PRIMARY'){
		
		$tableKeysData .= ' PRIMARY KEY(';
											  
		foreach($keyType as $keys=>$col){
			
			if(end($keyType) !== $col){
				$tableKeysData .= '`'.$col[0].'`, ';
			}else{
				$tableKeysData .= '`'.$col[0].'`';
			}
		}
		
		$tableKeysData .= ')'.$comma.''."\n";
		
	}else{

	  $tableKeysDataS = '';
										  
		foreach($keyType as $keys=>$col){
			if($keyType[$keys][1] == 0){
				$tableKeysDataT = ' UNIQUE KEY `'.$key.'`(';
			}else{
				$tableKeysDataT = ' KEY `'.$key.'`(';
			}

			if(end($keyType) !== $col){
				$tableKeysDataS .= '`'.$col[0].'`, ';
			}else{
				$tableKeysDataS .= '`'.$col[0].'`';
			}
		}
		
		$tableKeysDataS .= ')'.$comma.''."\n";
		$tableKeysData .= $tableKeysDataT.$tableKeysDataS;
		$tableKeysDataS = '';
		$tableKeysDataT='';
	}
	
}
unset($type);

		$databaseData[$table]['engine'] = $table_status[$counter]['Engine'];
		
		$ai = '';

		if($table_status[$counter]['Auto_increment'] !== ''){
			$ai = strtoupper($table_status[$counter]['Auto_increment']);
			
		}	
			
	  $exportData .= '-----------------------------------------------------------'."\n";
	  $exportData .= '--     STRUCTURE FOR TABLE :  '.$table.'              '."\n";
	  $exportData .= '-----------------------------------------------------------'."\n\n";
	  
	  $exportData .= 'DROP TABLE IF EXISTS `'.$table.'`;'."\n\r"; 
															   
	  $exportData .= 'CREATE TABLE IF NOT EXISTS `'.$table.'` ('."\n\r";  

foreach($tableFiels as $key=>$tableData){
		
		if($tableData["Null"] == 'NO'){
			$tableData["Null"] = 'NOT NULL';
		}else{
			$tableData["Null"] = 'DEFAULT NULL';
		}
		
		$default = "";
		
		if(empty($tableData["Default"])){
			
			if($key !== 0){
				$default = "DEFAULT ''";
			}
			
		}elseif($tableData["Default"] == '0'){
			
			$default = "DEFAULT '0'";
			
		}else{
			
			$default = "DEFAULT '".$tableData['Default']."'";

		}
		
		if(!empty($ai)){
			$default = $default." AUTO_INCREMENT";
			$increment = 'AUTO_INCREMENT='.$ai;
			$ai = '';
		}
		
	 	 $exportData .= '`'.$tableData["Field"].'` '.$tableData["Type"].' '.$tableData["Null"].' '.$default.','."\n";
		
		 $lastElement = end(array_keys($tableFiels));
		 $lastElement = $tableFiels[$lastElement]["Field"];
		
		 if($lastElement == $tableData["Field"]){
				
			$exportData .=  $tableKeysData;
			$exportData .= ")ENGINE=".$databaseData[$table]['engine']." DEFAULT CHARSET=".$charset[0]['Value']." ".$increment.";"."\n";
	  		
		}
	}
	
		  $counter++;
}

if($compression){
	$ext = ".sql.gz";
}else{
	$ext = ".sql";
}
$backupFile = $this->dbname ."__". date("Y-m-d-H-i-s");

$matchBackupDestination = is_array($backupPath);
//$matchBackupDestination = preg_match("/http|https|ftp/", $backupPath);
if(!$matchBackupDestination){
				
	if($backupPath == 'download'){
		
		if($compression){
				header("Cache-Control: no-cache, must-revalidate"); 
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
				header('Content-Description: File Transfer');
				header('Content-Type: application/x-gzip');
				header('Content-Disposition: attachment; filename='. $backupFile.$ext); 
				
				if (!extension_loaded('zlib')) {
						
						$exportData = gzencode($exportData, 9);
						echo $exportData;
		
				}else{
					
					if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')){
						ob_start("ob_gzhandler");
					}else{
						 ob_start(); 
					}
						echo $exportData;
			
					ob_end_flush();
					
				}		
		}else{
				header('Content-Description: File Transfer');
				header('Content-Type: text/plain');
				header('Content-Disposition: attachment; filename='. $backupFile.$ext); 
				echo $exportData;
			
		}
	}else{
			$checkDir = is_dir($backupPath."/");
			
			$check = true;
			
			if(!$checkDir){

				$check = mkdir($backupPath."/", 0644, true);
				
			}
				if(!$check){
					
						$this->errorMessage("Not possible to create directory with path defined. Possibly incorect path");
						exit;
				}else{
						if($compression){
						$exportData = gzencode($exportData, 9);
						}
						$wFile = fopen($backupPath."/".$backupFile.$ext,'w');
						
						file_put_contents($backupPath."/".$backupFile.$ext, $exportData);
						
						fclose($wFile); 
					
				}
			
		
	}
}else{
	
// preg_match("/http|https|ftp/", strtolower($backupPath[0]), $matchAddress);
 			
			$backupDir = "backup/";
			
 			$checkDir = is_dir(dirname(__FILE__)."/".$backupDir);
			
			$check = true;
			
			if(!$checkDir){

				$check = mkdir(dirname(__FILE__)."/".$backupDir,  0644, true);
				
			}
				if(!$check){
						
						$this->errorMessage("Not possible to create backup directory.");
						
				}else{
					
						if($compression){
						
						$exportData = gzencode($exportData, 9);
						}
							
						$wFile = fopen($backupDir.$backupFile.$ext,'w');
						
						file_put_contents($backupDir.$backupFile.$ext, $exportData);
						
						fclose($wFile); 
					
				}
			

	//$data = $tr->backupDatabase(array('ftp', 'localhost', 'database', 'bogyvet', '654321'), true, 'te_edu_info_novi');
if($backupPath[0] == 'ftp' || $backupPath[0] == 'ftps'){

	if(!extension_loaded('curl')) {
		$wFile = fopen($backupDir.$backupFile.$ext,'rb');
		
		$curl_init = curl_init();
		curl_setopt($curl_init, CURLOPT_URL, "".$backupPath[0]."://".$backupPath[3].":".$backupPath[4]."@".$backupPath[1].":".$backupPath[5]."/".$backupPath[2]."/".$backupFile.$ext);
		
		if( $backupPath[0] == 'ftps'){
		
		curl_setopt($curl_init, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_init, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($curl_init, CURLOPT_FTP_SSL, CURLOPT_FTPSSLAUTH);
		//curl_setopt($curl_init, CURLOPT_SSLVERSION, 3);
		
		}
		curl_setopt($curl_init, CURLOPT_UPLOAD, 1);
		curl_setopt($curl_init, CURLOPT_INFILE, $wFile);
		curl_setopt($curl_init, CURLOPT_INFILESIZE, filesize($backupDir.$backupFile.$ext));
		curl_exec ($curl_init);
		$curl_error = curl_error($curl_init);
		curl_close ($curl_init);

	

		
        if(!empty($curl_error)) {
			$this->errorMessage($curl_error);
        }
	
		fclose($wFile);
		
		
	}elseif(!extension_loaded('ftp')) {

		$connectionCreate = @ftp_connect($backupPath[1], $backupPath[5]);
		
		if(!$connectionCreate){
			
		 	$this->errorMessage("Not possible to connect to ftp: 
								- server : ".$backupPath[1]."  
								- port : ".$backupPath[5]."");
			exit;		
		}
		
		$ftpLogin = @ftp_login($connectionCreate, $backupPath[3], $backupPath[4]); 
		
		if(!$ftpLogin){
			
		 	$this->errorMessage("Not possible to login  ftp: 
								- server : ".$backupPath[1]."\n  
								- port : ".$backupPath[5]."
								- user : ".$backupPath[3]." 
								- password : ".$backupPath[4]." 
								
								");
			exit;		
		}
		ftp_pasv($connectionCreate, true);

		$uploadBackup = ftp_put($connectionCreate, $backupPath[2]."/".$backupFile.$ext, $backupDir.$backupFile.$ext, FTP_BINARY); 
		return $uploadBackup;
		if (!$uploadBackup) { 
		
			$this->errorMessage("FTP upload failed ftp:
								- server : ".$backupPath[1]."  
								- port : ".$backupPath[5]." 
								- source file path: ".$backupDir.$backupFile.$ext." 
								- destination file name: ". $backupFile.$ext."");
			exit;	
		} 
	
	  ftp_close ($connectionCreate);


	
	}else{
	//RADI SA FTPS
$wFile = fopen($backupDir.$backupFile.$ext,'rb');

$opts = array(  'ftp'=>array('overwrite'=> true));  
  $context = stream_context_create($opts);  

@file_put_contents("".$backupPath[0]."://".$backupPath[3].":".$backupPath[4]."@".$backupPath[1].":".$backupPath[5]."/".$backupPath[2]."/".$backupFile.$ext, 
					$wFile,  
					FILE_APPEND);	

	
	
	
	
//$wFile = @file_get_contents("".$backupPath[0]."://".$backupPath[3].":".$backupPath[4]."@".$backupPath[1].":".$backupPath[5]."/classicalmusic.te-edu.com/Untitled-1.jpg"); 
//
//$opts = array(  'ftp'=>array('overwrite'=> true));  
//  $context = stream_context_create($opts);  
//@file_put_contents("".$backupPath[0]."://".$backupPath[3].":".$backupPath[4]."@".$backupPath[1].":".$backupPath[5]."/".$backupPath[2]."/Untitled-1.jpg", 
//					$wFile,  
//					false, $context);	



		}
}elseif($backupPath[0] == 'http' || $backupPath[0] == 'https'){
	
	if(extension_loaded('curl')) {

		
		

		$backupPath[3] = base64_encode($backupPath[3]);
		$backupPath[4] = sha1($backupPath[4]);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		
		if( $backupPath[0] == 'https'){
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		
		}

		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "".$backupPath[0]."://".$backupPath[3].":".$backupPath[4]."@".$backupPath[1].":80/".$backupPath[2]."/upload.php");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("file"=>"@$wFile"));
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($backupDir.$backupFile.$ext));

		$response = curl_exec($ch);
		$curl_error = curl_error($ch);

		curl_close ($ch);

        if(!empty($curl_error)) {
			$this->errorMessage($curl_error);
        }
		 preg_match("/Warning/", $response, $matchError);

        if($matchError) {
		
			$this->errorMessage("Error occured during uploading backup file to server");
        }

		
	}else{
		
		//OTHER WAY TO UPLOAD
	}
							  
}elseif($matchAddress[0] == 'https'){
	
		if(extension_loaded('curl')) {



		}else{
			
			//OTHER WAY TO UPLOAD
			
		}
}
}

}

public function debugg($query, $operation) {
	$id = array();
	$id []= mysql_thread_id($this->connection);
	
	echo mysql_info($this->connection);
	if(prev($id) == current($id)){
	echo $query."<br />";
	echo $operation."<br />";
	}else{
   //print_r($query);
	echo $this->query_id."<br />";
	echo $this->host."<br />";
	echo $this->dbname."<br />";	
	echo $this->user."<br />";
	echo $this->pass."<br />";
	echo $this->query_id."<br />";
	echo $query."<br />";
	echo $operation."<br />";
	}
	//echo $this->pass."<br />";  $this->query_id
      // Useful during development for debugging  purposes.  Simple dumps a $this->dbname
      // query to the screen in a table.
 
//      $r = $this->select($sql);
//      if (!$r) return false;
//      echo "<div style=\"border: 1px solid blue; font-family: sans-serif; margin: 8px;\">\n";
//      echo "<table cellpadding=\"3\" cellspacing=\"1\" border=\"0\" width=\"100%\">\n";
//      
//      $i = 0;
//      while ($row = mysql_fetch_assoc($r)) {
//         if ($i == 0) {
//            echo "<tr><td colspan=\"".sizeof($row)."\"><span style=\"font-face: monospace; font-size: 9pt;\">$sql</span></td></tr>\n";
//            echo "<tr>\n";
//            foreach ($row as $col => $value) {
//               echo "<td bgcolor=\"#E6E5FF\"><span style=\"font-face: sans-serif; font-size: 9pt; font-weight: bold;\">$col</span></td>\n";
//            }
//            echo "</tr>\n";
//         }
//         $i++;
//         if ($i % 2 == 0) $bg = '#E3E3E3';
//         else $bg = '#F3F3F3';
//         echo "<tr>\n";
//         foreach ($row as $value) {
//            echo "<td bgcolor=\"$bg\"><span style=\"font-face: sans-serif; font-size: 9pt;\">$value</span></td>\n";
//         }
//         echo "</tr>\n";
//      }
//      echo "</table></div>\n";
   }

public function errorMessage($message='') {
	if($this->connection > 0){
		$this->error = mysql_error($this->connection);
		$this->errno = mysql_errno($this->connection);
	}
	else{
		$this->error = mysql_error();
		$this->errno = mysql_errno();
	}
echo "
<div style='border: 2px solid #ccc; width: 800px; padding: 10px; z-index: 999; margin: auto'>
	<div style='font-weight: bold; color: red; text-align: center; padding-bottom: 10px'>DATABASE ERROR MESSAGE</div>
		<div style='border: 0.5px solid #ccc; height: 20px; padding: 5px; font-weight: bold; text-align: center'>".$message."</div>";
if(strlen($this->error) > 0){
	
echo "
<div style='border: 0.5px solid #ccc; height: 80px; padding: 5px; margin: 2px 0; font-weight: bold; text-align: center'>
	<div style='color: red'>ERROR DATA &nbsp;:  &nbsp; </div>
	<div>".$this->error."</div>
	<div><span style='color: red'>ERROR CODE  &nbsp;:  &nbsp; </span>".$this->errno."</div>
</div>
<div style='border: 0.5px solid #ccc; height: 20px; padding: 5px 5px 5px 60px; margin: 2px auto; font-weight: bold; text-align: center'>
	 DATE &nbsp;:  &nbsp;".date("l, F j, Y \a\\t g:i:s A")."
</div>
<div style='border: 0.5px solid #ccc; height: 40px; padding: 5px; margin: 2px 0; font-weight: bold; text-align: center'>
	<div style='color: red;'>SCRIPT ERROR SOURCE &nbsp;:  &nbsp;</div>
	<div style=''><a href='".$_SERVER['REQUEST_URI']."'> ".$_SERVER['REQUEST_URI']."</a></div>
</div>";
	}   
if(strlen(@$_SERVER['HTTP_REFERER'] )> 0){
echo "  
<div style='border: 0.5px solid #ccc; height: 40px; padding: 5px; margin: 2px 0; font-weight: bold; text-align: center'>
	<div style='color: red;'>SCRIPT ERROR REFFER &nbsp;:  &nbsp;</div>
	<div style=''><a href='".@$_SERVER['HTTP_REFERER']."'>".@$_SERVER['HTTP_REFERER']."</a></div>
</div>
</div>";
		}
	}
}


$data = new TIDEDatabase('localhost', 'bogyvet', '654321');
$respose = $data->show_tables('etranslate');
print_r('<pre>');
print_r($respose);
print_r('</pre>');
?>