<?php
require_once '../Model.php';

/**
* shoppint mall model
* class Sample
*/
class Sample extends Model{

	protected $table = 'Sample';

	protected $database = 'sample';

	protected $primary_key = 'Insert this table`s pk';

	/**
	 * @return Sample 
	 */
	public function getSamples()
	{
		$data = $this->select(array(
			'seq',
		));
		return $data->get();
	}
}
