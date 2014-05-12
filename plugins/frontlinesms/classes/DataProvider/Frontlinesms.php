<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * FrontlineSms Data Providers
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    DataProvider\FrontlineSms
 * @copyright  2013 Ushahidi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

class DataProvider_FrontlineSms extends DataProvider {

	/**
	 * Contact type user for this provider
	 */
	public $contact_type = Model_Contact::PHONE;

	/**
	 * Sets the FROM parameter for the provider
	 *
	 * @return int
	 */
	public function from()
	{
		// Get provider phone (FROM)
		// Replace non-numeric
		$this->_from = preg_replace('/\D+/', "", parent::from());

		return $this->_from;
	}

	/**
	 * @return mixed
	 */
	public function send($to, $message, $title = "")
	{

		// Prepare data to send to frontline cloud
		$data = array(
			"secret" => $this->_options['key'],
			"message" => $message,
			"recipients" => array(
				array(
					"type" => "address",
					"value" => $to
				)
			)
		);

		// Get the frontlinecloud api URL
		$url = $this->_options['frontlinecloud_api_url'];

		// Make a POST request to send the data to frontline cloud
		$request = Request::factory($url)
				->method(Request::POST)
				->post($data)
				->headers('Content-Type', 'application/json');
		try
		{
			$response = $request->execute();
			// Successfully executed the request
			if ($response->status === 200)
			{
				return array(Message_Status::SENT, $this->tracking_id(Message_Type::SMS));
			}
			else
			{
				// Log warning to log file.
				$status = $response->status;
				Kohana::$log->add(Log::WARNING, 'Could not make a successful POST request: :message  status code: :code',
					array(':message' => $response->messages[$status], ':code' => $status));
			}
		}
		catch(Request_Exception $e)
		{
			// Log warning to log file.
			Kohana::$log->add(Log::WARNING, 'Could not make a successful POST request: :message',
				array(':message' => $e->getMessage()));
		}

		return array(Message_Status::FAILED, FALSE);
	}
}