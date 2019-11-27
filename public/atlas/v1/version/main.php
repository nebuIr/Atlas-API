<?php

use \Dotenv\Dotenv;

class Version
{
	/**
	 * @var mysqli
	 */
	private $conn;

	public function __construct() {
        require_once __DIR__ . '/../../../../vendor/autoload.php';

        $dotenv = Dotenv::create(__DIR__ . '/../../../../');
        $dotenv->load();

        $db_host = getenv('DB_HOST');
        $db_name = getenv('DB_NAME');
        $db_user = getenv('DB_USER');
        $db_pass = getenv('DB_PASS');

		$this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
		if (!$this->conn) {
			die('Connection failed: ' . $this->conn->connect_error);
		}
    }

    public function generateJson()
    {
		$stmt = $this->conn->prepare('SELECT id, url, version, timestamp FROM version');
		$stmt->execute();

        $output = array();
        $return_arr = array();

        if ($stmt->num_rows() > 0) {
            while ($row = $stmt->fetch()) {
                $output['id'] = (int) $row['id'];
                $output['url'] = $row['url'];
                $output['version'] = $row['version'];
                $output['timestamp'] = $row['timestamp'];

                $return_arr[] = $output;
            }
        }

        $output_file = fopen(__DIR__ . '/output.json', 'wb') or die('Unable to open file!');
        fwrite($output_file, json_encode($return_arr));
    }

    public function mainSqlUpdate()
    {
        $this->getJsonAllUpdate();
        echo 'Version updated!';
    }

    public function getJsonAllUpdate()
    {
		$url = __DIR__ . '/../../../../backend/atlas/v1/version/posts.json';
		$json = file_get_contents($url);
		$data = json_decode($json, true);

		$stmt = $this->conn->prepare('SELECT * FROM version');
		$stmt->execute();

		$row_count = $stmt->num_rows();

		$stmt->close();

        if ($row_count) {
            if($row_count === 1) {
                foreach ($data as $item) {
                    $this->querySqlUpdate($item);
                }
            }
        } else {
            foreach ($data as $item) {
                $this->querySqlSet($item);
            }
        }
    }

    public function querySqlUpdate($item)
    {
		$stmt = $this->conn->prepare('UPDATE version SET url=?, version=?, timestamp=? WHERE id=0');
		$stmt->bind_param('ssi', $item['url'], $item['version'], $item['timestamp']);
		$stmt->execute();

        $this->generateJson();
    }

    public function querySqlSet($item)
    {
		$stmt = $this->conn->prepare('INSERT INTO version (url, version, timestamp) VALUES (?, ?, ?)');
		if ($stmt !== FALSE)
		{
			$stmt->bind_param('ssi', $item['url'], $item['version'], $item['timestamp']);
			$stmt->execute();
		}
		else
		{
			die('prepare() failed: ' . htmlspecialchars($this->conn->error));
		}

        $this->generateJson();
    }
}
