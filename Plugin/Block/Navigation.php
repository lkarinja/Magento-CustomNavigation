<?php 

namespace PlymDesign\CustomNavigation\Plugin\Block;

use Psr\Log\LoggerInterface;

use Magento\Framework\Data\Tree\NodeFactory;

use Magento\Theme\Block\Html\Topmenu;

class Navigation
{

	/**
	 * Determine whether to write to debug log
	 *
	 * @var bool
	 */
	private $use_debug = true;

	/**
	 * Logger Interface for writing to log files in \var\log\
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Node Factory for creating new entries in the navigation bar
	 *
	 * @var NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * Constructor for setting interfaces
	 *
	 * @param LoggerInterface $loggerInterface Logger Interface to be referenced and used
	 * @param NodeFactory $nodeFactory NodeFactory Object to be referenced and used
	 *
	 * @return void
	 */
	public function __construct(
		LoggerInterface $logger,
		NodeFactory $nodeFactory
	){
		$this->logger = $logger;
		$this->nodeFactory = $nodeFactory;
	}

	/**
	 * Method called before \Magento\Theme\Block\Html\Topmenu::getHtml() is called
	 *
	 * Intercepts HTML generation and adds custom links to the navigation bar
	 * Reads a CSV file with a Name, ID, and URL and creates a new entry in the navigation bar
	 *
	 * @param Topmenu $subject Topmenu object passed that contains the intercepted method
	 * @param string $outermostClass Default parameter from Topmenu::beforeGetHtml()
	 * @param string $childrenWrapClass Default parameter from Topmenu::beforeGetHtml()
	 * @param int $limit Default parameter from Topmenu::beforeGetHtml()
	 *
	 * @return void
	 */
	public function beforeGetHtml(Topmenu $subject, $outermostClass = '', $childrenWrapClass = '', $limit = 0)
	{
		//Write to Debug Log
		$this->debug('Plugin CustomNavigation executing');

		//Path to where the custom navigation links file should be (Top directory of module)
		$custom_links_path = realpath(__DIR__ . '/../..' . '/LINKS.csv');

		//If a custom links file is found
		if(file_exists($custom_links_path))
		{
			//Read contents of the CSV into an array
			$csv_data = array_map('str_getcsv', file($custom_links_path));

			//Get data from the CSV and parse each row found
			foreach($csv_data as $csv_data_item)
			{
				//Build an array containing the Name, ID, and URL of the custom entry
				$node_array = $this->buildNodeArray($csv_data_item);
				//If the array is not empty
				if(!empty($node_array)){
					//Create a new node with the data read from the file
					$node = $this->nodeFactory->create(
						[
							'data' => $node_array,
							'idField' => 'id',
							'tree' => $subject->getMenu()->getTree()
						]
					);
					//Add the new node to the menu
					$subject->getMenu()->addChild($node);
				//If the array was empty
				}else{
					//Write to Debug Log and include the faulty row
					$this->debug('There was an error parsing the row: ' . print_r($csv_data_item, true));
				}
			}
		}
	}

	/**
	 * Used to generate an array containing Name, ID, and URL of the custom entry
	 *
	 * Determines if all entries (Name, ID, and URL) are valid
	 * If so, build an array with the necessary data and return the array
	 * If not, log an error and include the incorrect entry
	 *
	 * @param mixed $item_array Array of the entries read from the CSV file
	 *
	 * @return mixed
	 */
	protected function buildNodeArray($item_array)
	{
		//If the 1st item in the array does not match a valid name
		if(!preg_match('(.+)', $name = $item_array[0]))
		{
			$this->debug('There was an error parsing the Name of the Custom Navigation entry: ' . $name);
			return [];
		}

		//If the 2nd item in the array does not match a valid id
		if(!preg_match('(^([A-Za-z0-9]*-?)+$)', $id = $item_array[1]))
		{
			$this->debug('There was an error parsing the ID of the Custom Navigation entry: ' . $id);
			return [];
		}

		//If the 3rd item in the array does not match a valid URL
		if(!preg_match('(^https?:\/\/([A-Za-z0-9]+)(\.[A-Za-z0-9]+)+)', $url = $item_array[2]))
		{
			$this->debug('There was an error parsing the URL of the Custom Navigation entry: ' . $url);
			return [];
		}

		//Return an array containing the data that was successfully read
		return [
			'name' => __($name),
			'id' => $id,
			'url' => $url,
			'has_active' => false,
			'is_active' => false
		];
	}

	/**
	 * Method for writing to /var/log/debug.log
	 *
	 * If and only if $use_debug is true, write to log
	 *
	 * @param string $data Data to write to log
	 *
	 * @return bool true if data was logged, false if data was not logged
	 */
	private function debug($data){
		if($this->use_debug == true){
			$this->logger->debug($data);
			return true;
		}else{
			return false;
		}
	}
}