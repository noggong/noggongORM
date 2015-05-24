<?php

/**
* 페이징을 위한 클래스
* @todo 기존에 다른 서비스 만들때 만들어 놓은것 가져와서 필요한곳만 수정해서 사용함 후에 필요하면 더 수정해야함.
* class Paging
*/
class PagingModel {

	/**
	* @var model
	*/
	private $model;

	/**
	* @var attribute
	*/
	private $attribute;

	/**
	*  현재 페이지
	* @var curruent_page
	*/
	private $current_page;

	/**
	*  모델 프라이머리키
	* @var pk
	*/
	private $pk;

	/**
	* 페이지당 노출 될 게시물 수
	* @var perpage
	*/
	private $perpage;

	/*
	* 페이징 html template
	* @var paging_template
	*/
	private $paging_template;
	/**
	* 페이징 절삭 단위
	* @var range
	*/
	private $range_page;

	public function __CONSTRUCT($model, $current_page, $perpage, $range_page, $pk, $statics_field)
	{
		$model_class = get_class($model);
		$this->model = new $model_class($model->conn());
		$this->current_page = $current_page;
		$this->perpage = $perpage;
		$this->range_page = $range_page;
		$this->pk = $pk;
	}
	
	/**
	* range_page getter
	* @return int
	*/
	public function rangePage() 
	{
		return $this->range_page;
	}
	/**
	* Gets data in paged format
	*
	* @param string $sql
	* @param int $page
	* @param type $order
	* @param type $order_by
	* @return typed
	*/
	public function getPagedData($sql, $page = 1, $order_query = '')
	{
	   $page_info   = self::getPagingInfo($sql, "id", $page);
	   $limit_query = ($page_info['_LimitQuery']) ? $page_info['_LimitQuery'] : "" ;

	   $results = DB::select(DB::raw($sql . $order_query . $limit_query));
	   return array('results' => $results, 'page_info' => $page_info);
	}

	/**
	* @param $page_info
	* @return string
	*/
	public function getPagenationtHTML($page_info, $url)
	{
	   //check prepage number
	   if ($this->attribute->now_page <= 1) {
		   $pre_page = 1;
	   } else {
		   $pre_page = $this->attribute->now_page - 1;
	   }

	   //check nextpage number
	   if (($this->attribute->now_page + 1) >= $this->attribute->total_page) {
		   $next_page = $this->attribute->total_page;
	   } else {
		   $next_page = $this->attribute->now_page + 1;
	   }

	   return '<div class="tablenav-pages">' .
				'<span class="displaying-num">' . number_format($this->attribute->totalCnt) . '&nbsp;items</span>' .
				'<span class="pagination-links">' .
					'<a href="' . $url . '" class="first-page disabled page-btn"  title="Go to the first page" goto=1 >' .
						'<button class="btn btn-info">«</button>' .
					'</a>' .
		 		    '<a href="' . $url . '" class="prev-page disabled page-btn" title="Go to the previous page" goto=' . $pre_page . ' >' .
						'<button class="btn btn-info">‹</button>' .
					'</a>' .
					'<span class="paging-input">' .
						'<input class="current-page" title="Current page" type="text" name="page" value="' . $this->attribute->now_page . '" size="2"> of <span class="total-pages">' . number_format($this->attribute->_total_page) . '</span> ' .
					'</span>' .
				    '<a href="' . $url  .'" class="next-page page-btn" title="Go to the next page" goto=' . $next_page .' >' .
						'<button class="btn btn-info">›</button>' .
					'</a>' .
					'<a href="' . $url . '" class="last-page page-btn" title="Go to the last page" goto=' . $this->attribute->total_page . ' >' .
						'<button class="btn btn-info">»</button>
					</a>' .
				'</span>' . 
			    '</div>';
	}

	/**
	* Build paginiation info for data.
	* @param string $sql
	* @return bool|Array
	*/
	public function getPagingInfo($sql)
	{
//		print_r($sql);
	   $selector = array();

	   //기본 토탈 카운트
	   $selector[] = 'COUNT(' . $this->pk . ') as cnt';
	   
	   //인수로 받은 $statics 처리

	   if ( ! empty($statics)) {
		   foreach ($statics as $value) {
			   $selector[] = $value;
		   }
	   }
	   $rst = $this->model->table('(' . $sql . ') as t')->select($selector)->first()->attribute();
	   $this->attribute = new stdClass();

	   $this->attribute->total_cnt = (int) $rst->cnt;
	   $this->attribute->per_page = $this->perpage;

	   //추가로 받은 static값들을 배열에 넣어준다.
	   if ( ! empty($statics)) {
		   foreach ($rst as $field_name => $val) {

			   //cnt 는 $phiInfo 에  이미 자동 배열 저장 되므로 continue 한다.
			   if ($field_name == 'cnt') {
				   continue;
			   }
			   $this->attribute->$field_name = $val;
		   }
	   }

	   //현재 페이지 파라미터로 안받았을경우
	   if (empty($this->current_page) || $this->current_page ==false || (int) $this->current_page < 1) {
		   $this->current_page = 1;
	   }

	   $this->attribute->now_page = (int) $this->current_page;


	   if ($this->attribute->total_cnt < $this->attribute->per_page) {
			$this->attribute->total_page = 1;
	   } else {
		   $this->attribute->total_page = ceil($this->attribute->total_cnt / $this->attribute->per_page);
	   }

	   if ($this->attribute->now_page > $this->attribute->total_page) {
		   $this->attribute->now_page = $this->attribute->total_page;
	   }

	   //set Zone
		##스타트페이지
		$this->attribute->start_page = (ceil($this->attribute->now_page / $this->range_page) - 1) * $this->range_page + 1;

		##영역에서 끝페이지
		$this->attribute->end_page = $this->attribute->start_page + ($this->range_page - 1);


		if($this->attribute->end_page >= $this->attribute->total_page) {
			$this->attribute->end_page = $this->attribute->total_page;

		};

		##이전영역 가기
		/** 현재 페이지와 시작 페이지가 같다면 이전 그룹으로 간다. */
		if ($this->attribute->now_page == $this->attribute->start_page) {

			$this->attribute->pre_group = $this->attribute->start_page - $this->range_page;
			/** 이전 그룹이 1보다 작으면 1페이지가 무조건 그룹시작 페이지 이다. */
			if ($this->attribute->pre_group <= 1) {
				$this->attribute->pre_group = 1;
			}
		} else {
			/** @var int pre_group 그룹 시작 페이지와 현재 페이지가 같지 않다면 현재 그룹의 첫페이지로 간다.*/
			$this->attribute->pre_group = $this->attribute->start_page;

		}
		##다음영역가기'
		$this->attribute->next_group = ($this->attribute->end_page + 1);

	   $this->attribute->start_limit = (($this->attribute->now_page - 1) * $this->attribute->per_page + 1) - 1;
	   $this->attribute->limit_query = " Limit " . $this->attribute->start_limit . ", " . $this->attribute->per_page;
	   $this->attribute->start_row = $this->attribute->total_cnt - ($this->attribute->per_page * ($this->attribute->now_page - 1));

	}

	/**
	* Build paginiation info for data.
	* @param string $sql
	* @return bool|Array
	*/
	public function getPagingInfoByCount($count)
	{
	   $this->attribute->total_cnt = (int) $count;
	   $this->attribute->per_page = $this->perpage;

	   //추가로 받은 static값들을 배열에 넣어준다.
	   if ( ! empty($statics)) {
		   foreach ($rst as $field_name => $val) {

			   //cnt 는 $phiInfo 에  이미 자동 배열 저장 되므로 continue 한다.
			   if ($field_name == 'cnt') {
				   continue;
			   }
			   $this->attribute->$field_name = $val;
		   }
	   }

	   //현재 페이지 파라미터로 안받았을경우
	   if (empty($this->current_page) || $this->current_page ==false || (int) $this->current_page < 1) {
		   $this->current_page = 1;
	   }

	   $this->attribute->now_page = (int) $this->current_page;


	   if ($this->attribute->total_cnt < $this->attribute->per_page) {
			$this->attribute->total_page = 1;
	   } else {
		   $this->attribute->total_page = ceil($this->attribute->total_cnt / $this->attribute->per_page);
	   }

	   if ($this->attribute->now_page > $this->attribute->total_page) {
		   $this->attribute->now_page = $this->attribute->total_page;
	   }

	   //set Zone
		##스타트페이지
		$this->attribute->start_page = (ceil($this->attribute->now_page / $this->range_page) - 1) * $this->range_page + 1;

		##영역에서 끝페이지
		$this->attribute->end_page = $this->attribute->start_page + ($this->range_page - 1);


		if($this->attribute->end_page >= $this->attribute->total_page) {
			$this->attribute->end_page = $this->attribute->total_page;

		};

		##이전영역 가기
		$this->attribute->pre_group = $this->attribute->start_page;
		##다음영역가기'
		$this->attribute->next_group = ($this->attribute->end_page + 1);
	   $this->attribute->start_limit = (($this->attribute->now_page - 1) * $this->attribute->per_page + 1) - 1;
	   $this->attribute->limit_query = " Limit " . $this->attribute->start_limit . ", " . $this->attribute->per_page;

	}
	/**
	* 아트리뷰트 요소 가져온다.
	* @return string|stdclass
	*/
	public function attribute($key = null) {
		if ($key) {
			return $this->attribute->$key;
		} else {
			return $this->attribute;
		}
	}

	/**
	* 페이징 html 노출
	* 바로 display 된다
	* @param string $parameter 페이지이동할때 전달되어야할 파라미터 문자열
	* @param string $page_parameter_name 페이징 될때 페이지 파라미터 이름
	* @param array $data 템플릿에 넘겨줘야할 변수명을 키로하여 배열로 받는다
	* @param string $paging_template
	*/
	public function getDisplayPaging($parameter = false, $page_parameter_name = 'page', $data = array(), $paging_template = ''){

		if (is_array($data)) {
			extract($data);
		}

		if($this->attribute->total_cnt < 0){
			return '';
		}

		if (empty($paging_template)) {
			$paging_template = dirname(__FILE__) . '/template/default.html';
		}

		$parameter = preg_replace("/[&]".$page_parameter_name."=[0-9]+/", "", $parameter);

		if(substr($parameter, 0, 1) == '&') {
			$parameter = preg_replace("/&/", "", $parameter, 1);
		}

		require $paging_template;


	}

}