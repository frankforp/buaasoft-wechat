<?php 

//class_schedule_test();
/**
 * learn how to use ClassSchedule from this function
 */
function class_schedule_test() {
	global $wxdb;
	require_once("../../config.php");
	
	$_1221 = new ClassSchedule(ClassSchedule::TABLE_CLASS_SCHEDULE, 1221);

    // use to edit and save content
	$_1221->set_weekday(1)->add_class("英语口语", "s1e2k3j4g5");
	echo $_1221->save() ? "save success" : "save fail";

    // use to clean all data
	$_1221->clean();
	echo "\n";
	print_r($_1221->get_schedule_days()); // must be empty

    // use to query the content in database
	echo "\n";
	print_r($_1221->query(1)); // must have content
}

/**
 * Class ClaaSchedule
 *
 * the class schedule database model, for query and other data operation
 *
 * Database Scheme:class_schedule
 * 1. id (int PK, A_I)
 * 2. weekday (int 星期几 e.g. 1)
 * 3. classification (int 年纪 e.g. 1221)
 * 4. class_1 (string 第一节课信息 e.g. 英语口语#s1e2k3j4g5)
 * ......
 *
 * @todo changing according to wxdb.php
 */
class ClassSchedule {
	
	// store info. the sub_index is weekday
	private $schedule_days;
	// current weekday
	private $cur_weekday;
	// classification
	private $classification;
    // table name
    private $table_name;
	// max class num
	const NUM_MAX_CLASS = 12;
	const SEPARATOR = '#';
	// the database table name
	const TABLE_CLASS_SCHEDULE = "class_schedule";

    /**
     * set the classification
     * @param $classification
     */
    function __construct($table_name, $classification) {
		$this->cur_weekday = -1;
        $this->table_name = $table_name;
		$this->classification = $classification;
		$this->schedule_days = array();
	}

	/**
	 * add a class which classification is $classification and weekday is $cur_weekday
	 *
	 * @param string $classStr the information of class, e.g. 英语口语 J1-101
	 * @param string $infoStr the information of class time, e.g. s1e2k3j4g5
	 * @return the instance of ClassSchedule
	 * @todo 尝试根据数组中已有的内容来调整冲突内容？
	 */
	public function add_class($classStr, $infoStr) {
		$count = count($this->schedule_days[$this->cur_weekday]);
		$this->schedule_days[$this->cur_weekday]["class_".++$count] = $classStr.self::SEPARATOR.$infoStr;
		return $this;
	}

	/**
     * create database table with no check
     * @param string $table_name The name of database table
	 * @todo check if the schema is right
	 */
    public static function create_table($table_name) {
		global $wxdb;
        $table_schema = <<<SQL
CREATE TABLE `{$table_name}`
(id int NOT NULL AUTO_INCREMENT,
 weekday int NOT NULL,
 classification int NOT NULL,
 class_1 varchar(100),
 class_2 varchar(100),
 class_3 varchar(100),
 class_4 varchar(100),
 class_5 varchar(100),
 class_6 varchar(100),
 class_7 varchar(100),
 class_8 varchar(100),
 class_9 varchar(100),
 class_10 varchar(100),
 class_11 varchar(100),
 class_12 varchar(100),
 PRIMARY KEY (`id`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1
SQL;

		$wxdb->query($table_schema);
	}

    public static function drop_table($table_name) {
        global $wxdb;
        $wxdb->query("DROP TABLE $table_name");
    }

    /**
     * try to make a 5 (weekday) * 12 (class) array
     *
     * @deprecated this function is not completed
     * @param $classes_all_weekday
     */
    public static function normalize($classes_all_weekday) {
        $normalized_classes = array();

        for ($weekday = 1; $weekday <= 5; $weekday++) {
            $normalized_classes[$weekday] = array();

            $classes = $classes_all_weekday[$weekday];
            for ($i = 1; $i < self::NUM_MAX_CLASS; $i++) {
                $class = $classes["class_".$i];
                $class_info = explode(ClassSchedule::SEPARATOR, $class);
                $info_str = $class_info[1];
                $info = self::parse_info_str($info_str);
            }
        }
    }

    /**
     * 根据信息字符串来获得课程信息
     *
     * sample input : s1e2k3j4g5
     * @param string $info_str a string contain the class information
     * @return array 返回解析 $info_str 后获得的包含信息的数组
     */
    public static function parse_info_str($info_str) {
        // store the info
        $info["s"] = array();
        $info["e"] = array();
        $info["w"] = array();
        $info["k"] = array();
        $info["j"] = array();
        $info["g"] = array();

        $patterns["s"] = '/s([0-9]+)/';
        $patterns["e"] = '/e([0-9]+)/';
        $patterns["w"] = '/w([0-9]+)/';
        $patterns["k"] = '/k([0-9]+)/';
        $patterns["j"] = '/j([0-9]+)/';
        $patterns["g"] = '/g([0-9]+)/';

        $match = array();
        foreach ($patterns as $key => $pattern) {
            $times = preg_match($pattern, $info_str, $match);
            if ($times == 1) {
                $info[$key][count($info[$key])] = $match[1];
            }
        }

        return $info;
    }

	/**
	 * clean all data is RAM, not the database
     * @see delete
	 */
	public function clean() {
		$this->cur_weekday = -1;
		$this->schedule_days = array();
	}

	/**
	 * delete all data in database
	 * @return bool return true if success, or false.
	 */
	public function delete() {
		global $wxdb;
		$wxdb->query("TRUNCATE TABLE $this->table_name");
		return true;
	}

	/**
	 * set the weekday
	 * 
	 * @see $cur_weekday
	 * @param int $weekday 
	 * @return object the instance of classSchedule
	 */
	public function set_weekday($weekday) {
		$this->cur_weekday = $weekday;
		// prepare the array
		if (!isset($this->schedule_days[$weekday]) || !is_array($this->schedule_days[$weekday])) {
			$this->schedule_days[$weekday] = array();
		}

		return $this;
	}

	/**
	 * get all schedule
	 */
	public function get_schedule_days() {
		return $this->schedule_days;
	}

	/**
	 * query according to the $weekday and $classification
	 * $classification is specified in construct function
	 * 
	 * @param int $weekday the weekday number
	 * @return array the array contain class information, or an empty array
	 */
	public function query($weekday) {
        $result = null;
		if (isset($this->schedule_days[$weekday])) {
			$result = $this->schedule_days[$weekday];
		} else {
			global $wxdb;
			$results = $wxdb->get_results("SELECT * FROM class_schedule WHERE weekday=$weekday AND classification=$this->classification", ARRAY_A);
			if ($wxdb->num_rows == 1) {
				$result = $results[0];
			} else {
				$result = array();
			}
		}

		return $result;
	}

	/**
	 * query all weekday
	 */
	public function query_all_weekday() {
		$result = array();
		for ($weekday = 1; $weekday <= 5; $weekday++) {
			$result[$weekday] = $this->query($weekday);
		}
		return $result;
	}

	/**
	 * save all weekday content to database
	 */
	public function save() {

		global $wxdb;
		$weekdays = array_keys($this->schedule_days);
		foreach ($weekdays as $weekday) {
            if (count($this->schedule_days[$weekday])) {
                $wxdb->query("SELECT * FROM $this->table_name WHERE classification=$this->classification AND weekday=$weekday");
                $class_count = count($this->schedule_days[$weekday]);
                $where = array(
                    "classification"=>$this->classification,
                    "weekday"=>$weekday
                );

                $result = false;
                // clear first, according to the $classification and $weekday to update database content
                if ($wxdb->num_rows == 1) {
                    // need update
                    $result = $wxdb->update($this->table_name, $this->schedule_days[$weekday], $where);
                } else {
                    // need insert
                    $result = $wxdb->insert($this->table_name, array_merge($this->schedule_days[$weekday], $where));
                }

                if ($result === false) {
                    return false;
                }
            }
		}
        return true;
		
	}
}

?>