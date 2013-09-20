<?php

class Sphinxrt {

	const DB_DRIVER = 'mysql';
	const HOST = '127.0.0.1';
	const PORT = '9306';
	const USER_NAME = 'sphinx';


	private $_connect;

	private $_data = array();
	private $_query = '';

	private $_transState = false;
	private $_transErrors = 0;

	private $_errorMessage = '';
	private $_errorNumber = 0;

	private function _setConnect()
	{
		$this->_connect = new PDO(
			self::DB_DRIVER.':host='.self::HOST.';port='.self::PORT.';dbname=',
			self::USER_NAME,
			''
		);

		return $this;
	}
	private function _getConnect()
	{
		return $this->_connect;
	}

	private function _setData($data)
	{
		$this->_data = $data;
		return $this;
	}
	private function _getData()
	{
		return $this->_data;
	}

	private function _setQuery($query)
	{
		$this->_query = $query;
		return $this;
	}
	private function _getQuery()
	{
		return $this->_query;
	}

	private function _setTransState($state)
	{
		if (!is_bool($state))
		{
			$state = false;
		}

		$this->_transState = $state;

		return $this;
	}
	private function _getTransState()
	{
		return $this->_transState;
	}

	private function _getTransErrors()
	{
		return $this->_transErrors;
	}
	private function _incTransErrors()
	{
		$this->_transErrors++;
		return $this;
	}
	private function _resetTransErrors()
	{
		$this->_transErrors = 0;
		return $this;
	}

	private function _setErrorMessage($message)
	{
		if ($message===null)
		{
			$message = '';
		}

		$this->_errorMessage = $message;

		return $this;
	}
	public function errorMessage()
	{
		return $this->_errorMessage;
	}
	private function _resetErrorMessage()
	{
		$this->_setErrorMessage('');
		return $this;
	}

	private function _setErrorNumber($number)
	{
		if ($number===null)
		{
			$number = 0;
		}

		$this->_errorNumber = $number;

		return $this;
	}
	public function errorNumber()
	{
		return $this->_errorNumber;
	}
	private function _resetErrorNumber()
	{
		$this->_setErrorNumber(0);
		return $this;
	}

	private function _resetQueryAndData()
	{
		$this
			->_setData(array())
			->_setQuery('');

		return $this;
	}

	private function _enableTrans()
	{
		$this->_setTransState(true);
		return $this;
	}

	private function _disableTrans()
	{
		$this
			->_setTransState(false)
			->_resetTransErrors();

		return $this;
	}

	private function _setError($error)
	{
		$this
			->_setErrorMessage($error[2])
			->_setErrorNumber($error[1]);

		return $this;
	}

	private function _resetError()
	{
		$this
			->_resetErrorMessage()
			->_resetErrorNumber();

		return $this;
	}

	private function _registerTrans($errorReport)
	{
		if (
			$this->_getTransState() &&
			$errorReport!==0
		)
		{
			$this->_incTransErrors();
		}

		return $this;
	}


	function __construct()
	{
		$this->_setConnect();
	}

	public function insert($index, $data)
	{
		$this
			->_setData($data)
			->_filterData($index);
		$fields = $this->_getDataKeys();

		$this->_prepareData();
		$values = $this->_getDataKeys();

		return $this
			->_setQuery("INSERT INTO ".$index." (".$fields.") VALUES (".$values.")")
			->_query();
	}

	public function replace($index, $data)
	{
		$this
			->_setData($data)
			->_filterData($index);
		$fields = $this->_getDataKeys();

		$this->_prepareData();
		$values = $this->_getDataKeys();

		return $this
			->_setQuery("REPLACE INTO ".$index." (".$fields.") VALUES (".$values.")")
			->_query();
	}

	public function update($index, $data, $where)
	{
		$data = $this
			->_setData($data)
			->_filterDataForUpdate($index)
			->_getData();

		return $this
			->_prepareDataForUpdate()
			->_setQuery("UPDATE ".$index." SET ".$this->_getDataKeys().$this->_prepareWhere($where))
			->_setData($data)
			->_prepareData()
			->_query();
	}

	private function _filterData($index)
	{
		$schema = $this
			->_setQuery("DESC ".$index)
			->_queryWithoutParameters();
		$schema = array_unique($schema->fetchAll(PDO::FETCH_COLUMN, 0));

		$this->_dataIsMultidimensional() ? $this->_filterDataForMulti($schema) :$this->_filterDataForSimple($schema);

		return $this;
	}

	private function _filterDataForSimple($schema)
	{
		$data = $this->_getData();

		foreach($data as $key=>$value)
		{
			if (!in_array($key, $schema))
			{
				unset($data[$key]);
			}
		}

		$this->_setData($data);

		return $this;
	}

	private function _filterDataForMulti($schema)
	{
		$data = $this->_getData();

		foreach ($data as $dataItem)
		{
			foreach($dataItem as $key=>$value)
			{
				if (!in_array($key, $schema))
				{
					unset($dataItem[$key]);
				}
			}
			$newData[] = $dataItem;
		}

		$this->_setData($newData);

		return $this;
	}

	private function _filterDataForUpdate($index)
	{
		$data = $this->_getData();
		$allowedTypes = array('uint', 'bigint', 'float', 'multi', 'multi_64', 'timestamp');

		$fullSchema = $this
			->_setQuery("DESC ".$index)
			->_queryWithoutParameters()
			->fetchAll();

		foreach ($fullSchema as $field)
		{
			if(
				in_array($field[1], $allowedTypes) &&
				$field[0]!='id'
			)
			{
				$schema[] = $field[0];
			}
		}

		foreach($data as $key=>$value)
		{
			if (!in_array($key, $schema))
			{
				unset($data[$key]);
			}
		}

		$this->_setData($data);

		return $this;
	}

	public function delete($index, $ids)
	{
		if (is_array($ids))
		{
			$where = array('id IN' => $ids);
		}
		else
		{
			$where = array('id' => $ids);
		}

		return $this
			->_setQuery("DELETE FROM ".$index.$this->_prepareWhere($where))
			->_queryWithoutParameters();
	}

	public function truncate($index)
	{
		return $this
			->_setQuery("TRUNCATE RTINDEX ".$index)
			->_queryWithoutParameters();
	}

	public function deleteAll($index)
	{
		$ids = $this
			->_setQuery("SELECT id FROM ".$index."")
			->_queryWithoutParameters();
		$ids = implode(', ', $ids->fetchAll(PDO::FETCH_COLUMN, 0));

		return $this
			->_setQuery("DELETE FROM ".$index." WHERE id IN (".$ids.")")
			->_queryWithoutParameters();
	}

	public function optimize($index)
	{
		return $this
			->_setQuery("OPTIMIZE INDEX ".$index)
			->_queryWithoutParameters();
	}


	private function _getDataKeys()
	{
		$data = $this->_getData();

		if ($this->_dataIsMultidimensional())
		{
			$data = $data[key($data)];
		}

		return implode(',', array_keys($data));
	}

	private function _dataIsMultidimensional()
	{
		return is_numeric(key($this->_getData())) ? true : false;
	}

	private function _prepareData()
	{
		$data = $this->_getData();

		if ($this->_dataIsMultidimensional())
		{
			foreach ($data as $item)
			{
				$newData[] = $this->_prepareItem($item);
			}
		}
		else
		{
			$newData = $this->_prepareItem($data);
		}

		$this->_setData($newData);

		return $this;
	}

	private function _prepareItem($data)
	{
		foreach ($data as $key=>$value)
		{
			$item[':'.$key] = $value;
		}

		return $item;
	}

	private function _prepareDataForUpdate()
	{
		$data = $this->_getData();

		foreach ($data as $key=>$value)
		{
			$newData[$key.'=:'.$key] = $value;
		}

		$this->_setData($newData);

		return $this;
	}

	private function _prepareWhere($where)
	{
		$whereString = empty($where) ? '' : ' WHERE ';
		$delimiter = '';

		foreach ($where as $key=>$value)
		{
			$operator = $this->_hasOperator($key) ? ' ' : ' = ';
			$value = $this->_prepareValue($value);

			$whereString .= $delimiter.$key.$operator.$value;
			$delimiter = ' AND ';
		}

		return $whereString;
	}

	private function _hasOperator($string)
	{
		$string = trim($string);
		if (preg_match("/(\s|<|>|!|=|in|not in|match)/i", $string))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function _prepareValue($value)
	{
		$dbConnect = $this->_getConnect();

		$valueType = strtolower(gettype($value));

		switch($valueType)
		{
			case 'array':
				foreach ($value as $item)
				{
					$valueEscaped[] = is_numeric($item) ? $item : $dbConnect->quote($item);
				}
				$value = "(".implode(', ', $valueEscaped).")";
				break;

			case 'null':
				$value = '';
				break;

			default:
				$value = is_numeric($value) ? $value : $dbConnect->quote($value);
				break;
		}

		return $value;
	}

	private function _query()
	{
		return $this->_dataIsMultidimensional() ? $this->_queryMulti() : $this->_querySimple();
	}

	private function _queryWithoutParameters()
	{
		$this->_resetError();

		$connect = $this->_getConnect();

		$query = $connect->prepare($this->_getQuery());
		$query->execute();

		$this
			->_setQuery('')
			->_setError($query->errorInfo())
			->_registerTrans($this->errorNumber());

		return $this->errorNumber()===0 ? $query : false;
	}

	private function _querySimple()
	{
		$this->_resetError();

		$connect = $this->_getConnect();
		$queryString = $this->_getQuery();

		$query = $connect->prepare($queryString);
		$data = $this->_getData();
		foreach ($data as $key=>$value)
		{
			$value = $this->_convertType($value);
			$query->bindValue($key, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$query->execute();

		$this
			->_resetQueryAndData()
			->_setError($query->errorInfo())
			->_registerTrans($this->errorNumber());

		return $this->errorNumber()===0 ? $query : false;
	}

	private function _queryMulti()
	{
		$this->_resetError();

		$connect = $this->_getConnect();
		$queryString = $this->_getQuery();

		$query = $connect->prepare($queryString);
		$data = $this->_getData();
		foreach ($data as $item)
		{
			foreach ($item as $key=>$value)
			{
				$value = $this->_convertType($value);
				$query->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
			}
			$query->execute();

			$this
				->_setError($query->errorInfo())
				->_registerTrans($this->errorNumber());
		}

		$this->_resetQueryAndData();

		return $this->errorNumber()===0 ? $query : false;
	}

	private function _convertType($value)
	{
		if (gettype($value)=='string')
		{
			if (is_numeric($value))
			{
				if (substr_count($value, '.'))
				{
					$value = (float)$value;
				}
				else
				{
					$value = (int)$value;
				}
			}
		}

		return $value;
	}

	public function transBegin()
	{
		$connect = $this->_getConnect();
		$query = $connect->prepare("BEGIN");
		$query->execute();

		$this->_enableTrans();

		return $this;
	}

	public function transCommit()
	{
		$connect = $this->_getConnect();
		$query = $connect->prepare("COMMIT");
		$query->execute();

		$this->_disableTrans();

		return $this;
	}

	public function transRollback()
	{
		$connect = $this->_getConnect();
		$query = $connect->prepare("ROLLBACK");
		$query->execute();

		$this->_disableTrans();

		return $this;
	}

	public function transComplete()
	{
		if ($this->transStatus())
		{
			$this->transCommit();
			return true;
		}
		else
		{
			$this->transRollback();
			return false;
		}
	}

	public function transStatus()
	{
		if (
			$this->_getTransState() &&
			$this->_getTransErrors()>0
		)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

}

?>