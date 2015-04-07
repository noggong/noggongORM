# noggongORM
php version 5.2 ORM 

##How to use
PDO 가 아닌 Mysql API 기반. 

 
###Database Object 생성
	
	<?php
	require_once 'Model.php'; /*원하는 디렉토리 에 압축을 풀고 Model.php 를 경로에 맞게 `require` 한다.*/

	/**
	* Object model
	* class Object
	*/
	class Object extends Model{

		protected $table = '[테이블 이름]'; 

		protected $database = '[데이터 베이스 이름]';

		
	}

###Object 인스턴스 생성

$db_conn : optional
	
	<?php
	$object = new Object($db_conn = '[DB connection]');

##Method

**Query Builder** 라고 명시되어 있는 메소드는 select 를 하기전 쿼리를 셋팅하는 메소드로 `Model` 을 반환하며 순서에 상관없이 체인하여 사용할수 있다 
####conn()
여러 모델 object 사용시 connection 자원을 아끼기 위해 connection 을 받아와 인스턴시 생성시 넘겨준다.

 @return -  db connection  

 
	<?php
	$new_object = new NewObject($object->conn());

####where($field, $value, $seperate = '=') **Query Builder**
 and where 절 추가

 @param string $field 필드명

 @param string $value 값

 @param string $seperate 연산자

 @return Model

	<?php
	$obj->where('id', 1)->where('created_at', now(), '>');

####orWhere($field, $value, $seperate = '=') **Query Builder**	

-or where 절 추가

@param string $field 필드명

@param string $value 값

@param string $seperate 연산자

@return Model

	<?php
	$obj->where('user_name', 'noggong')->orWhere('created_at', now(), '>');

####orderby($field, $ascend = 'ASC') **Query Builder**

정렬 선택

@param string $field 필드이름

@param string $ascend 차순 정의

@return Model

	<?php
	$obj->orderby('created', 'desc');

	
####groupby($array) **Query Builder**

 groupby 절
 
@param array $array 필드이름
 
@return Model

	$obj->orderby('created', 'desc')->groupby(array('fromurl', 'created_at'));



####rawWhere($string) **Query Builder**
스트링 그대로 `where` 절추가 , 정형화된 계산식 이외 사용할 때 사용 

@param string $field 필드명

@param string $value 값

@param string $seperate 연산자

@return Model

	<?php
	$obj->rawWhere('seq is not null');
 


####page($current_page = 1, $perpage = 30, $range = 10, $statics_field = false) **Query Builder**

페이징 기능

@param int $current_page 현재 페이지

@param int $perpage 페이지당 노출될 row 수

@param int $range 하단에 페이징에 노출되는 페이징 단위

@param int $statics_field 페이징시 추가로 가져와야 하는 Db 정보

@return Model
	
	<?
	$obj->page(2, 6); /*2페이지의 6개의 게시물 가져옴*/ 


####page_info($current_page = 1, $perpage = 30, $range = 10, $statics_field = false) 

**select query 실행없이 page 정보**만 가져온다.

@param int $current_page 현재 페이지

@param int $perpage 페이지당 노출될 row 수

@param int $range 하단에 페이징에 노출되는 페이징 단위

@param int $statics_field 페이징시 추가로 가져와야 하는 Db 정보

@return PagingModel

 
	<?php
	$obj->where('seq', '1')->page_info(1, 10); /* seq 가 1인 게시물을 가져온다고 했을때 첫 페이지의 10개의 게시물 가져오는 페이징 정보*/


####count($field = null)
row count 가져오기

@param string $field count  할 필드

@return int

	<?php
	$obj->where('seq', '1')->page_info(1, 10); /* seq 가 1인 게시물의 갯수를 가져온다*/


####insert($array)

insert 한다

@param array $array

@return resource|false
	
	<?php
	$obj->insert(
			array(
				'user_name' => $user_name, 
				'title' => $title,
			)	
		);




####update($array)

update 한다, `where`절이 미리 셋팅 되어있지 않다면 실행되지 않는다. `return` 으로 update 된 열의 **첫번째 열의 값**을 **model 객체**로 `return`한다 

@param array $array

@return Object

	<?php
	/* seq 가 1 인 값의 title을 변경한다 */
	$obj->
		where('seq', 1)->
		update(array('title' => $title));


####first()

@return Model
첫번째 결과값만 가져온다
	
	<?php
	/** seq 가 10 인 값중 첫번째 값을 반환한다 **/
	$obj->where('seq', 10)->first();


####find($pk_value) 
Pk 에 의한 select 를한다.

@param int|mixed $pk_value 

@return Model

	<?php
	/* obj 모델에서 pk 값이 4인 row를 반환한다 */
	$obj->find(4);


####limit($x, $y = null) **Query Builder**
limit 절 설정

@param int $x limit x,y 에서 x

@param int $y limit x,y 에서 y

@return Model

	/** row중 4번째 row 부터 5개의 게시물을 가져온다*/
	$obj->limit(4,5)'

	/** row중 첫번째 row 부터  5개의 게시물을 가져온다*/
	$obj->limit(5)'


####select($field_list) **Query Builder**
select 될 필드 설정, query builder 에서 해당 메소드가 생략될경우 **애스트릭(\*)** 으로 된다. 

@param array|string $field_list 필드 목록

@return Model

	<?php
	/** seq 필드를 select 필드 로 설정한다**/
	$obj->select('seq');

	/** seq, title 필드를 select 필드 로 설정한다**/
	$obj->select(array('seq', 'title'));

####get()

query builder 로 생성된 query 를 select 한다.

@return Model
	
	<?php
	$obj->get();	



####attribute($key = null)
attribute 반환, `get()` 에 의해 `select` 되면 attribute 프로퍼티에 결과값이 저장되는데 해당 값을 가져온다. 

@param string|mixed key atrribute key -> 비어있으면 전제 attribute 값을 가져온다.

@return array|stdClass

	<?php
 	$result = $obj->select('seq')->get()->attribute();
	echo $result[0]->seq; 
	

####table($table) **Query Builder**
 
커스텀 테이블 셋팅, 모델의 table 이외 다른 테이블을 사용해야 할경우 쓴다. (서브쿼리를 이용할때 주로 사용)

@param string $table

@return Model

	<?php
	/* select * from another_table 결과값에서 select 한다 */
	$obj->table('select * from another_table')->get();


####history()
모든 쿼리는 profileing 되고 히스토리에 남는다. 쌓여있는 쿼리 히스토리 를 반환한다.

@return array 

	<?php
	$obj->history();


####getLastQuery()
history 중 마지막 쿼리 실행 결과 반환

@return array 

	<?php
	$obj->getLastQuery();


####toSql() 
query builder 로 만들어진 쿼리를 실행전 문자열로 가져온다.

@return string 

	<?php
	
	echo $obj->select(array('seq', 'user'))->where('seq', '1')->toSql();

	/** output : 'select seq, user from table where seq=1' **/
	




####toArray($array = null)

select 된 결과물 배열 형태로 반환한다. 

@param array $array 특정 값들을 변경해서 저장 ,해당 값은 $val 로 표

@return array 

	<?php
	/** 결과 값중 create_at 은 substr 함수를 실행하여 치환 한 값을 배열로 반환한다 **/
	$obj->get()->toArray(
		array(
			'created_at' => 'substr($val, 0, 10)',
	));



####hasMany($relationModel, $primary_key = '', $foreign_key = '')

1: m 관계일때 for문 돌리면서 가져오기

@param string $relationModel 관계에 있는 모델이름

@param string $primary_key 연결되어있는 키 

@param string $foreign_key 연결되어있는 모델의 fk

@return Model

#####Object.php

	<?php

	/**
	* Object model
	* class Object
	*/
	
	class Object extends Model{

		protected $table = '[테이블 이름]'; 

		protected $database = '[데이터 베이스 이름]';

		public function fkObject() 
		{
			return $this->hasMany('fkObjeModel
		}

#####controller.php
	/** object 의 row를 select 한후 fk로 걸려있는 fkObject 모델에 row를 select 하여 relationship 프로퍼티에 저장한다.**/
	$obj = new Object();
	$obj->get()->fkObject;



####protected function hasOne($relationModel, $foreign_key = '', $primary_key = '')
1: 1 관계일때 for문 돌리면서 가져오기

@param string $relationModel 관계에 있는 모델이름

@param string $foreign_key 연결되어있는 모델의 fk

@param string $primary_key 연결되어있는 키 

@return Model


 
#####Object.php

	<?php

	/**
	* Object model
	* class fkObject
	*/
	
	class fkObject extends Model{

		protected $table = '[테이블 이름]'; 

		protected $database = '[데이터 베이스 이름]';

		public function Object() 
		{
			return $this->hasOne('ObjeModel
		}

#####controller.php
	/** fkobject 의 row를 select 한후 자신의 fk 로 걸려있는 Object 모델에 row pk 와 연결하여 relationship 프로퍼티에 저장한다.**/
	$obj = new fkObject();
	$obj->get()->Object;



##For example

	$obj = new Object();
	
	/* user가 noggong 이고 2014 이후에 작성된 row를 seq만 가져오되 페이징 하여 첫페이지를 가져온다*/
	$get = $obj->select('seq')->where('user', 'noggong')->where('created_at' , '2014', '>')->paging(1,10)->get();

	/**위에서 가져온 값들을 array 형태로 반환한다 **/
	$get->toArray();

	/** 부모 글(parent_seq)이 4 인 댓글의 갯수를 가져온다 **/
	$obj->where('parent_seq', 4)->count();


	/**
	* created_at 을 기준으로 DESC 형태로 가져오되 $current_page 를 가져오고 해당 댓글의 원본 글을 가져온다
	* card() 는 model 에 미리 hasOne으로 선언되어있어야 한다. 위의 hasOne 메소드를 참고
	**/
	$obj->orderby('created_at', 'DESC')->
	page($current_page, 20)->
	get()->card();
	
#####페이징 HTML 노출

	$obj-paging->attribute();

	/*
	아래 값들을 통해 template 에서 사용할 수 있다.
	output : stdclass {
		total_cnt, /**전체 row 수 **/
		per_page, /**페이지당 노출  row 수 **/
		now_page, /**현재 페이지 **/
		total_page, /** 총 페이지 **/
		start_page, /** 화면에 노출될 페이지 중 첫페이지 **/
		end_page, /** 화면에 노출될 페이지 중 마지막 페이지 **/
		pre_group, /** 이전 그룹으로 갈때 이동 될 페이지 **/
		next_group, /** 다음 그룹으로 갈때 이동 될 페이지 **/
		start_limit, /** 화면에 노출되는 row 중 첫 row **/
		limit_query /** 쿼리에 limit 구절에 들어갈 구문 **/
	}
	*/

	/*노출 되어야 할부분에 해당 코드 삽입*/
	<?php 
	* @param string $parameter 페이지이동할때 전달되어야할 파라미터 문자열
	* @param string $page_parameter_name 페이징 될때 페이지 파라미터 이름
	* @param array $data 템플릿에 넘겨줘야할 변수명을 키로하여 배열로 받는다
	* @param string $paging_template html 템플릿 파일
	$obj->paging->getDisplayPaging($_SERVER['QUERY_STRING'], 'page');
	?>
