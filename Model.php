<?php
require_once dirname(__FILE__) . '/PagingModel.php';

/**
 * @auth 우준호
 * @date 2015.03.27
 * @description PDO 가 설치가 되어 있지 않아 일단 Original MySQL API 로 작성
 * Orm 일단 급하게 심플한 기능들 위주로 작성
 * class Model
 */
class Model
{

    /**
     * @var 테이블 이름
     */
    protected $table;

    /**
     * @var 커스텀 테이블
     */
    protected $custom_table;

    /**
     * @var 데이터 베이스
     */
    protected $database;

    /**
     * @var 프라이머리키
     */
    protected $primary_key;

    /**
     * @var insert 시에 생성시간 필드 false: 넣지 않음
     */
    protected $create_date = 'created_at';

    /**
     * @var update 시에 생성시간 필드 false: 넣지 않음
     * 보통 default current_date on update current_Date 형식으로 넣어주면 DB에서 자동 처리된다.
     */
    protected $update_date = false;

    /**
     * @var db_connect
     */
    private $conn;

    /**
     * @var stdClass result of select query
     */
    private $attribute;

    /**
     * @var array 쿼리가 실행되면 history 에 남김
     */
    private $history = array();

    /**
     * @var string 쿼리로 생성된 문자열
     */
    private $query_string;

    /**
     * @var resource mysql_query 로 실행된 쿼리 결과 리소스
     */
    private $resource;

    /**
     * @var 객체내에서 자동 실행되는 mysql_Query 에 대한 일회성 resource
     */
    private $temp_resource;

    /**
     * @var 쿼리 where 절
     */
    private $where = array();

    /**
     * @var 쿼리 gruop by 절
     */
    private $groupby = array();

    /**
     * @var 쿼리 order by 절
     */
    private $orderby = array();

    /**
     * @var 쿼리 limit 절
     */
    private $limit;

    /**
     * raw string으로 사용해야할 문자열
     */
    private $raw_string = array();

    /**
     * @var string select 시 가져올 필드
     */
    private $select = '*';

    /**
     * @var array 필드
     */
    private $coulmn = array();

    /**
     * @var paging class
     */
    public $paging;

    /**
     * @var static db _conn
     */
    static $static_db_conn;

    /**
     * 생성자
     */
    public function __CONSTRUCT($db = false)
    {


        if (!$db) {


            $this->conn = Model::staticConnect($this->database);
        } else {

            $this->conn = $db;
        }
        $this->coulmn();
    }

    static function staticConnect($database)
    {

        $db_info_all = './db_conf.php';

        if (empty(Model::$static_db_conn)) {

            Model::$static_db_conn = new stdClass();


            Model::$static_db_conn->db_info = $db_info_all[$database];
            Model::$static_db_conn->database = $database;

            Model::$static_db_conn->connection = Model::dbConnection(Model::$static_db_conn->db_info['host'],
                Model::$static_db_conn->db_info['user'],
                Model::$static_db_conn->db_info['pass'],
                Model::$static_db_conn->db_info['db']
            );


        } else {
            if ($database != Model::$static_db_conn->database) {
                Model::$static_db_conn->db_info = $db_info_all[$database];
                Model::$static_db_conn->database = $database;

                Model::$static_db_conn->connection = Model::dbConnection(Model::$static_db_conn->db_info['host'],
                    Model::$static_db_conn->db_info['user'],
                    Model::$static_db_conn->db_info['pass'],
                    Model::$static_db_conn->db_info['db']
                );
            }
        }

        return Model::$static_db_conn->connection;

    }

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $dbname
     * @return resource
     */
    static function dbConnection($host, $user, $pass, $dbname)
    {

        $conn = mysql_connect($host,
            $user,
            $pass,
            true
        ) or die("Failed connecting to MySQL...   ");

        mysql_select_db($dbname, $conn);
        mysql_query('set names utf8');
        mysql_query('set profiling=1');

        return $conn;
    }

    /**
     * 테이블에 필드 가져오는 명령어
     */
    private function coulmn()
    {

        if (empty($this->coulmn)) {
            $coulmn = $this->query('desc ' . $this->table, false)->
            fetch_object($this->temp_resource)->toArray();
            foreach ($coulmn as $row) {
                if ($row['Key'] == 'PRI' && empty($this->primary_key)) {
                    $this->primary_key = $row['Field'];
                }
                $this->coulmn[$row['Field']]['type'] = $row['Type'];
                $this->coulmn[$row['Field']]['null'] = $row['Null'];
                $this->coulmn[$row['Field']]['default'] = $row['Default'];

            }
        }
    }

    /**
     * mysql connect
     * @param string $database 테이터베이스 이름
     */
    private function connect($database = '')
    {
        $db_info_all = require_once $_SERVER['DOCUMENT_ROOT'] . '/_Lib/db_conf/db_conf.php';
        if (empty($database)) {
            $database = $this->database;
        }

        $db_info = $db_info_all[$database];

        $this->conn = mysql_connect($db_info['host'],
            $db_info['user'],
            $db_info['pass'], true
        ) or die("Failed connecting to MySQL...   ");

        mysql_select_db($db_info['db'], $this->conn);
        $this->query("set names utf8", false);
        $this->query('set profiling=1', false);

    }

    /**
     * db 커넥션 리턴
     * @return object
     */
    public function conn()
    {
        return $this->conn;
    }

    /**
     * 데이터 전부 가져오기
     * @return Model
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * and where 절 추가
     * @param string $field 필드명
     * @param string $value 값
     * @param string $seperate 연산자
     * @return $this
     */
    public function where($field, $value, $seperate = '=')
    {
        $where_string = '';
        if (!empty($this->where)) {
            $where_string .= ' AND ';
        }

        $where_string .= $field . ' ' .$seperate . ' "' . $this->escape($value) . '"';

        $this->where[] = $where_string;
        return $this;
    }

    /**
     * or where 절 추가
     * @param string $field 필드명
     * @param string $value 값
     * @param string $seperate 연산자
     * @return $this
     */
    public function orWhere($field, $value, $seperate = '=')
    {
        $where_string = '';
        if (!empty($this->where)) {
            $where_string .= ' OR ';
        }
        $where_string .= $field . $seperate . '"' . $this->escape($value) . '"';

        $this->where[] = $where_string;
        return $this;
    }

    /**
     * 정렬 선택
     * @param string $field 필드이름
     * @param string $ascend 차순 정의
     * @return $this
     */
    public function orderby($field, $ascend = 'ASC')
    {
        $this->orderby[] = $field . '|' . $ascend;

        return $this;
    }

    /**
     * groupby 절 선택
     * @param array $array 필드이름
     * @return $this
     */
    public function groupby($array)
    {
        $this->groupby = array_merge($this->groupby, $array);

        return $this;
    }

    /**
     * 스트링 그대로 where 절추가
     * @param string $string mysql 내 문자열
     * @return Model
     */
    public function rawWhere($string, $type = 'AND')
    {
        $where_string = '';
        if (!empty($this->where)) {
            $where_string .= ' ' . $type . ' ';
        }
        $where_string .= $string;

        $this->where[] = $where_string;
        return $this;
    }

    /**
     * @param int $current_page 현재 페이지
     * @param int $perpage 페이지당 노출갯수
     * @param int $range 페이지 넘버 범위
     * @param bool $statics_field 불러와야 할 정적 필드
     * @return $this
     */
    public function page($current_page = 1, $perpage = 30, $range = 10, $statics_field = false)
    {
        $this->paging = new PagingModel($this, $current_page, $perpage, $range, $this->primary_key, $statics_field);

        return $this;

    }

    /**
     * 페이징 기능
     * @return PagingModel
     */
    public function page_info($current_page = 1, $perpage = 30, $range = 10, $statics_field = false)
    {
        $paging = new PagingModel($this, $current_page, $perpage, $range, $this->primary_key, $statics_field);

        $paging->getPagingInfoByCount($this->count());
        return $paging;

    }

    /**
     * row count 가져오기
     * @param string $field count  할 필드
     * @return int
     */
    public function count($field = null)
    {
        if (empty($field)) {
            $field = $this->primary_key;
        }
        $this->select('COUNT(' . $field . ') as cnt ');

        $this->first();

        return $this->attribute->cnt;
    }

    /**
     * insert 한다
     * @param array $array
     * @return resource|false
     */
    public function insert($array)
    {
        if (!empty($this->create_date)) {
            $array[$this->create_date] = date('Y-m-d H:i:s');
        }

        $array = array_map(array($this, 'escape'), $array);

        $data_string_array = array();

        foreach ($array as $key => $val) {

            if (in_array($val, $this->raw_string)) {
                $data_string_array[] = $key . '=' . $val . '';
            } else {
                $data_string_array[] = $key . '="' . $val . '"';
            }
        }

        $data_string = implode(', ', $data_string_array);

        $this->query_string = 'INSERT INTO ' . $this->table . ' SET ' . $data_string;
        //error_log($this->query_string);
        $this->query($this->query_string);
        return $this->resource;
    }

    /**
     * @param array $array
     * @return int
     */
    public function insertGetId(array $array)
    {
        $this->insert($array);
        return mysql_insert_id($this->conn);
    }

    /**
     * @param int $pk_value
     * @return $this|bool
     */
    public function del($pk_value)
    {
        if (!$pk_value) {
            return false;
        }

        $this->query_string = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primary_key . '= ' . $pk_value;
        $this->query($this->query_string);

        return $this;
    }

    /**
     * where 설정으로 인한 삭제
     * 삭제 후 삭제된 게시물을 반환한다.
     * @return Model
     */
    public function delByWhere()
    {
        //만약을 위해 where절이 없으면 false된다.
        if (!$this->where) {
            return false;
        }
        $temp_where = $this->where;
        $delete_row = $this->get();
        $this->query_string = 'DELETE FROM ' . $this->table . ' WHERE ' . implode(' ', $temp_where);
        $this->query($this->query_string);

        $this->where = $temp_where;

        return $delete_row;
    }
    /**
     * update 한다
     * @param array $array
     * @return Model
     */
    public function update($array)
    {
        //만약을 위해 where절이 없으면 false된다.
        if (!$this->where) {
            return false;
        }

        if (!empty($this->update_date)) {
            $array[$this->update_date] = date('Y-m-d H:i:s');
        }
        $array = array_map(array($this, 'escape'), $array);

        $data_string_array = array();
        foreach ($array as $key => $val) {

            if (in_array(stripslashes($val), $this->raw_string)) {
                $data_string_array[] = $key . '=' . stripslashes($val) . '';
            } else {

                $data_string_array[] = $key . '="' . $val . '"';
            }
        }

        $data_string = implode(', ', $data_string_array);
        $temp_where = $this->where;

        $this->query_string = 'UPDATE ' . $this->table . ' SET ' . $data_string . ' WHERE ' . implode(' ', $this->where);
        $this->query($this->query_string);

        $this->where = $temp_where;
        return $this->first();
    }

    /**
     * @param $array
     * @return false|resource|this
     */
    public function updateOrInsert($array)
    {

        if (array_key_exists($this->primary_key, $array) && !empty($array[$this->primary_key])) {
            return $this->where($this->primary_key, $array[$this->primary_key])->update($array);
        } else {
            return $this->insertGetId($array);
        }
    }

    /**
     * 첫번째 결과값만 가져온다
     * @return $this
     */
    public function first()
    {
        $this->orderby($this->primary_key, 'DESC')->limit(1)->get();
        if (!empty($this->attribute[0])) {
            $this->attribute = $this->attribute[0];
        };

        return $this;

    }

    /**
     * Pk 에 의한 select
     * @param int|mixed $pk_value
     * @return $this
     */
    public function find($pk_value)
    {
        $data = $this;
        if (!empty($this->select)) {
            $data = $data->select($this->select);
        }
        $data->where($this->primary_key, $pk_value)->get();
        if (!empty($data->attribute[0])) {
            $data->attribute = $data->attribute[0];
        };

        return $data;
    }

    /**
     * limit 절 설정
     * @param int $x limit x,y 에서 x
     * @param int $y limit x,y 에서 y
     * @return $this
     */
    public function limit($x, $y = null)
    {
        $this->limit = $x;
        if ($y) {
            $this->limit .= ' , ' . $y;
        }

        return $this;
    }

    /**
     * 만들어진 쿼리문 실행한다.
     * @return $this
     */
    public function get()
    {
        $this->query()->fetch_object();
        return $this;
    }

    /**
     * attribute 반환
     * @param string|mixed key atrribute key
     * @return array|stdClass
     */
    public function attribute($key = null)
    {
        if ($key) {
            if (is_array($this->attribute)) {
                return $this->attribute[$key];
            } else if (is_object($this->attribute)) {
                return $this->attribute->$key;
            } else {
                return '';
            }

        } else {
            return $this->attribute;
        }
    }

    /**
     * select 구문 생성
     * @param array|string $field_list 필드 목록
     * @return $this
     */
    public function select($field_list)
    {
        if (is_array($field_list)) {
            $select = array();
            foreach ($field_list as $field) {
                $select[] = $field;
            }
            $this->select = implode(', ', $select);
        } else if (!empty($field_list)) {
            $this->select = $field_list;
        }

        return $this;
    }

    /**
     * 문자열 그대로 쿼리 실행
     * @param string $query
     * @return $this
     */
    public function rawExec($query)
    {

        return $this;
    }

    /**
     * 실행된 쿼리 기록
     * @param string $query 쿼리 문자열
     */
    private function saveHistory($query)
    {
        $idx = sizeof($this->history);

        $this->history[$idx]['error'] = mysql_error();
        $profile = $this->query('show profiles', false)->fetch($this->temp_resource);

        $this->temp_resource = null;

        $this->history[$idx]['Query_ID'] = $profile->Query_ID;
        $this->history[$idx]['Duration'] = $profile->Duration;
        $this->history[$idx]['Return_time'] = date('Y-m-d H:i');
        $this->history[$idx]['Query'] = $query;

    }

    /**
     * 커스텀 테이블 셋팅
     * @param string $table
     * @return $this
     */
    public function table($table)
    {
        $this->custom_table = $table;

        return $this;
    }

    /**
     * 쿼리 히스토리 반환
     * @return array
     */
    public function history()
    {
        return $this->history;
    }

    /**
     * 마지막 쿼리 실행 결과 반환
     * @return array
     */
    public function getLastQuery()
    {
        return (!empty($this->history[sizeof($this->history) - 1])) ?
            $this->history[sizeof($this->history) - 1] :
            null;
    }

    /**
     * 만들어진 쿼리 스트링 가져오기
     * @return string
     */
    public function toSql()
    {
        if (empty($this->query_string)) {
            $this->makeQueryString();
        }
        return $this->query_string;
    }

    /**
     * 쿼리 실행기
     * @param string $query
     * @param bool $save_history 히스토리에 남기는가 체크
     * @return $this
     */
    private function query($query = '', $save_history = true)
    {
        if (empty($query)) {
//			echo '<xmp>';print_r($this);echo '</xmp>';

            if (empty($this->query_string)) {
                $this->makeQueryString();
            }
            $query = $this->query_string;
        }

        if ($save_history) {
            $this->resource = mysql_query($query, $this->conn);
            $this->saveHistory($query);
        } else {
            $this->temp_resource = mysql_query($query, $this->conn);
        }
        $this->formatQueryString();
        return $this;
    }

    /**
     * 모델의 property 를 이용하여 query 문을 만든다.
     */
    private function makeQueryString()
    {
        $this->query_string = '';
        $table = (!empty($this->custom_table)) ? $this->custom_table : $this->table;

        $this->query_string .= 'SELECT ' . $this->select . ' FROM ' . $table;

        if (!empty($this->where)) {
            $this->query_string .= ' WHERE ' . implode(' ', $this->where);
        }

        if (!empty($this->groupby)) {
            $this->query_string .= ' GROUP BY ' . implode(', ', $this->groupby);
        }

        if (!empty($this->orderby)) {
            $this->query_string .= ' ORDER BY ';

            $order_list_arr = array();
            foreach ($this->orderby as $order) {
                $order_arr = explode('|', $order);
                $order_list_arr[] = $order_arr[0] . ' ' . $order_arr[1];
            }
            $this->query_string .= implode(' , ', $order_list_arr);
        }
        /**
         * limit 와 paging 은 같이 쓰일수 없고 limit 를 우선한다.
         */
        if (!empty($this->limit)) {
            $this->query_string .= ' LIMIT ' . $this->limit;
        } else if ($this->paging) {
            $this->paging->getPagingInfo($this->query_string);
            $this->query_string .= $this->paging->attribute('limit_query');
        }
    }

    /**
     * mysql_query 결과물 할당
     * @param resource $resource mysql_query 한후 받은 리소스
     * @return Model
     */
    private function fetch_object($resource = null)
    {
        if (!$resource) {
            $resource = $this->resource;
        }

        $temp = array();
        $idx = 0;
        while ($row = $this->fetch($resource)) {

            if ($row) {
                foreach ($row as $key => $val) {
                    $temp[$idx]->{$key} = $val;
                }
            }
            $idx++;
        }
        $this->attribute = $temp;

        return $this;
    }

    /**
     * 받은 리소스를 한번만 fetch 해준다
     * @param resource $resource mysql_query 한후 받은 리소스
     * @param string $type select 결과 가져오는 방식
     */
    private function fetch($resource, $type = 'fetch_object')
    {
        $method = 'mysql_' . $type;
        return $method($resource);
    }


    /**
     * 결과물 배열 형태로 반환
     * @param array $array 특정 값들을 변경해서 저장
     * @return array
     */
    public function toArray($array = null)
    {
        $return = array();
        if (empty($this->attribute)) {
            return $return;
        }

        if (is_array($this->attribute)) {
            foreach ($this->attribute as $idx => $row) {
                foreach ($row as $field => $val) {
                    if (!empty($array[$field])) {
                        // eval 좋지 않은 방법이지만 5.2대의 php 에 클로져가 없다.
                        eval('$val = ' . $array[$field] . ';');
                        $return[$idx][$field] = $val;
                    } else {
                        $return[$idx][$field] = $val;
                    }
                }
            }
        } else if (is_object($this->attribute)) {
            foreach ($this->attribute as $field => $val) {
                if (!empty($array[$field])) {
                    // eval 좋지 않은 방법이지만 5.2대의 php 에 클로져가 없다.
                    eval('$val = ' . $array[$field] . ';');
                    $return[$field] = $val;
                } else {
                    $return[$field] = $val;
                }
            }
        } else {
            return $return;
        }

        return $return;
    }

    /**
     * 만들어진 쿼리 스트링 가져오기
     * @return string
     */
    public function getQueryString()
    {
        return $this->query_string;
    }

    /**
     * 직접 만든 쿼리문
     * @param string $query_string
     * @return $this
     */
    public function rawQueryExecute($query_string)
    {
        $this->query_string = $this->escape($query_string);
        return $this;
    }

    /**
     * 쿼리문 이스케이프 한다.
     * @param string $str
     * @return string
     */
    private function escape($str)
    {
        return mysql_real_escape_string($str);
    }

    /**
     * raw string 에 문자열 추가
     * @param string $val 문자열
     * @return string
     */
    public function rawString($val)
    {
        $this->raw_string[] = $val;
        return $val;
    }

    /**
     * 쿼리절 생성에 연관된 속성들 초기화
     */
    private function formatQueryString()
    {
        $this->where = array();
        $this->query_string = '';
        $this->select = '*';
        $this->orderby = '';
        $this->limit = '';
        $this->raw_string = array();
        $this->custom_table = '';
        $this->groupby = array();
    }

    /**
     * 1: m 관계일때 for문 돌리면서 가져오기
     * @param string $relationModel 관계에 있는 모델이름
     * @param string $primary_key 연결되어있는 키
     * @param string $foreign_key 연결되어있는 모델의 fk
     * @param array|bool $where 연결할때 where 추가
     * @param array|bool $orderby  연결할때 order 절 추가
     * @return Model
     */
    protected function hasMany($relationModel, $primary_key = '', $foreign_key = '', $where = false, $orderby = array())
    {
        if (empty($primary_key)) {
            $primary_key = $this->primary_key;
        }

        if (empty($foreign_key)) {
            $foreign_key = $this->table . '_' . $this->primary_key;
        }

        if (is_array($this->attribute)) {
            foreach ($this->attribute as $key => $row) {

                if (!isset($this->attribute[$key]->relationship)) {
                    $this->attribute[$key]->relationship = new stdClass();
                }
                /** @var Model $model */
                $model = new $relationModel($this->conn);
                $model = $model->where($foreign_key, $row->$primary_key);
                if (!empty($this->where)) {
                    foreach ($this->where as $where_raw_string) {
                        $model->rawWhere($where_raw_string);
                    }
                }

                if (!empty($where)) {
                    $model = $model->where($where[0], $where[1]);
                }

                if (!empty($orderby)) {
                    $model = $model->orderby($orderby[0], $orderby[1]);
                }
                $this->attribute[$key]->relationship->$relationModel = $model->get();


            }
        } else if (is_object($this->attribute)) {
            if (!isset($this->attribute->relationship)) {
                $this->attribute->relationship = new stdClass();
            }
            /** @var Model $model */
            $model = new $relationModel($this->conn);
            $model = $model->where($foreign_key, $this->attribute->$primary_key);

            if (!empty($this->where)) {
                foreach ($this->where as $where_raw_string) {
                    $model->rawWhere($where_raw_string);
                }
            }

            if (!empty($where)) {
                $model = $model->where($where[0], $where[1]);
            }
            if (!empty($orderby)) {
                $model = $model->orderby($orderby[0], $orderby[1]);
            }

            $this->attribute->relationship->$relationModel = $model->get();
        }

        $this->formatQueryString();
        return $this;
    }

    /**
     * 1: 1 관계일때 for문 돌리면서 가져오기
     * @param string $relationModel 관계에 있는 모델이름
     * @param string $foreign_key 연결되어있는 모델의 fk
     * @param string $primary_key 연결되어있는 키
     * @param array|bool $where 연결할때 where 추가
     * @return Model
     */
    protected function hasOne($relationModel, $foreign_key = '', $primary_key = '', $where = false)
    {
        $model = new $relationModel($this->conn);
        if (empty($foreign_key)) {
            $foreign_key = $model->table . '_' . $model->primary_key;
        }
        if (empty($primary_key)) {
            $primary_key = $model->primary_key;
        }

        if (is_array($this->attribute)) {
            foreach ($this->attribute as $key => $row) {
                if (!isset($this->attribute[$key]->relationship)) {
                    $this->attribute[$key]->relationship = new stdClass();
                }
                /** @var Model $model */
                $model = new $relationModel($this->conn);
                $model = $model->where($primary_key, $row->$foreign_key);
                if (!empty($this->where)) {
                    foreach ($this->where as $where_raw_string) {
                        $model->rawWhere($where_raw_string);
                    }
                }
                if (!empty($where)) {
                    $model = $model->where($where[0], $where[1]);
                }
                $this->attribute[$key]->relationship->$relationModel = $model->first();

            }
        } else if (is_object($this->attribute)) {

            if (!isset($this->attribute->relationship)) {
                $this->attribute->relationship = new stdClass();
            }

            /** @var Model $model */
            $model = new $relationModel($this->conn);

            if (!empty($this->where)) {
                foreach ($this->where as $where_raw_string) {
                    $model->rawWhere($where_raw_string);
                }
            }
            $model = $model->where($primary_key, $this->attribute->$foreign_key);
            if (!empty($where)) {
                $model = $model->where($where[0], $where[1]);

            }
            $this->attribute->relationship->$relationModel = $model->first();
        }

        $this->formatQueryString();
        return $this;
    }

    /**
     * @return $this
     */
    public function begin()
    {
        $this->query('begin', false);

        return $this;
    }

    /**
     * @return $this
     */
    public function rollback()
    {
        $this->query('rollback', false);

        return $this;
    }

    /**
     * @return $this
     */
    public function commit()
    {
        $this->query('commit', false);
        return $this;
    }

}
