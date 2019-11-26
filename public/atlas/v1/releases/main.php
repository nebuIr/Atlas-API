<?php

use \Dotenv\Dotenv;

class Releases
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
		$stmt = $this->conn->prepare('SELECT id, url, title, platform_pc, platform_ps4, platform_xbox, excerpt, image, body FROM releases ORDER BY id DESC');
		$stmt->execute();
        $output = array();
        $return_arr = array();

        if ($stmt->num_rows() > 0) {
            while ($row = $stmt->fetch()) {
                $output['id'] = (int) $row['id'];
                $output['url'] = $row['url'];
                $output['title'] = $row['title'];
				$output['platforms']['pc'] = (bool) $row['platform_pc'];
				$output['platforms']['ps4'] = (bool) $row['platform_ps4'];
				$output['platforms']['xbox'] = (bool) $row['platform_xbox'];
                $output['images']['image_large'] = $row['image'];
                $output['excerpt'] = $row['excerpt'];
                $output['body'] = $row['body'];

                array_push($return_arr, $output);
            }
        }

		$stmt->close();
        $this->conn->close();

        $output_file = fopen(__DIR__ . '/output.json', "w") or die('Unable to open file!');
        fwrite($output_file, json_encode($return_arr));
    }

    public function mainSql()
    {
        $this->getJson();
        echo 'Releases updated!';
    }

    public function getJson()
    {
        $url = __DIR__ . '/../../../../backend/atlas/v1/releases/posts.json';
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        foreach ($data as $item) {
            $this->querySql($item);
        }
    }

    public function querySql($item)
    {
        $this->querySqlSet($item);
        $this->querySqlUpdate($item);
    }

    public function querySqlSet($item)
    {
    	$pc = (int)$item['platforms']['pc'];
    	$ps4 = (int)$item['platforms']['ps4'];
    	$xbox = (int)$item['platforms']['xbox'];

		$stmt = $this->conn->prepare('INSERT INTO releases ("id", "url", "title", "platform_pc", "platform_ps4", "platform_xbox", "excerpt", "image", "body") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$stmt->bind_param('issiiisss', $item['id'], $item['url'], $item['title'], $pc, $ps4, $xbox, $item['excerpt'], $item['image'], $item['body']);
		$stmt->execute();
        var_dump($this->conn->connect_error);
    }

    public function querySqlUpdate($item)
    {
		$pc = (int)$item['platforms']['pc'];
		$ps4 = (int)$item['platforms']['ps4'];
		$xbox = (int)$item['platforms']['xbox'];

		$stmt = $this->conn->prepare('UPDATE releases SET url=?, title=?, platform_pc=?, platform_ps4=?, platform_xbox=?, excerpt=?, image=?, body=? WHERE id=?');
		$stmt->bind_param('ssiiisssi', $item['url'], $item['title'], $pc, $ps4, $xbox, $item['excerpt'], $item['image'], $item['body'], $item['id']);
		$stmt->execute();
		var_dump($this->conn->connect_error);

        $this->generateJson();
    }
}