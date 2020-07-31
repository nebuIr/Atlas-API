<?php
switch($section[1])
{
    case 'v1':
        switch($section[2])
        {
            case 'news':
                require_once __DIR__ . '/../src/v1/classes/News.php';
                $data = new News();
                switch($section[3])
                {
                    case '':
                        header('Content-Type: application/json');
                        isInt(['limit', 'offset']);
                        if (isset($_GET['order'])) {
                            if ('desc' === $_GET['order'] || 'asc' === $_GET['order']) {
                                echo json_encode($data->getJSONFromSQL($data->getItems($_GET['order']), false, $_GET));
                            } else if (empty($_GET['order'])) {
                                echo json_encode($data->getJSONFromSQL($data->getItems(), false, $_GET));
                            } else {
                                error(3, 'order', $_GET['order']);
                            }
                        } else {
                            echo json_encode($data->getJSONFromSQL($data->getItems(), false, $_GET));
                        }
                        break;

                    case ((bool)preg_match('/^\d+$/', $section[3]) === true):
                        header('Content-Type: application/json');
                        echo json_encode($data->getJSONFromSQL($data->getResultByID($section[3]),false, $_GET));
                        break;

                    case 'latest':
                        header('Content-Type: application/json');
                        echo json_encode($data->getJSONFromSQL($data->getItems(),true, $_GET));
                        break;

                    case $section[3] !== 'latest':
                        error(1, 'id', $section[3]);
                        break;

                    default:
                        error(0);
                        break;
                }
                break;

            case 'releases':
                require_once __DIR__ . '/../src/v1/classes/Releases.php';
                $data = new Releases();
                switch($section[3])
                {
                    case '':
                        header('Content-Type: application/json');
                        isInt(['limit', 'offset']);
                        if (isset($_GET['order'])) {
                            if ('desc' === $_GET['order'] || 'asc' === $_GET['order']) {
                                echo json_encode($data->getJSONFromSQL($data->getItems($_GET['order']), false, $_GET));
                            } else if (empty($_GET['order'])) {
                                echo json_encode($data->getJSONFromSQL($data->getItems(), false, $_GET));
                            } else {
                                error(3, 'order', $_GET['order']);
                            }
                        } else {
                            echo json_encode($data->getJSONFromSQL($data->getItems(), false, $_GET));
                        }
                        break;

                    case ((bool)preg_match('/^\d+$/', $section[3]) === true):
                        header('Content-Type: application/json');
                        echo json_encode($data->getJSONFromSQL($data->getResultbyId($section[3]),false, $_GET));
                        break;

                    case 'latest':
                        header('Content-Type: application/json');
                        $params = ['latest' => true];
                        echo json_encode($data->getJSONFromSQL($data->getItems(), true, $_GET));
                        break;

                    case $section[3] !== 'latest':
                        error(1, 'id', $section[3]);
                        break;

                    default:
                        error(0);
                        break;
                }
                break;

            case 'version':
                require_once __DIR__ . '/../src/v1/classes/Version.php';
                $data = new Version();
                if ($section[3] === '') {
                    header('Content-Type: application/json');

                    echo json_encode($data->getJSONFromSQL());
                } else {
                    error(0);
                }
                break;

            default:
                error(0);
                break;
        }
        break;

    default:
        include_once __DIR__ . '/../src/pages/home.php';
        break;
}

function isInt($input) {
    foreach ($input as $item) {
        if (isset($_GET[$item]) && (bool)preg_match('/^\d+$/', $_GET[$item]) === false) {
            error(2, $item, $_GET[$item]);
        }
    }
}

function error($type, $variable = null, $input = null) {
    $errors = [
        ['error' => 'Invalid URL', 'description' => 'Invalid URL provided. Please refer to the documentation: ' . getURL() . '/docs/v1/'],
        ['error' => 'Invalid type', 'description' => 'The path variable \'' . $variable . '\' needs to be of type integer, but we found \'' . $input . '\''],
        ['error' => 'Invalid type', 'description' => 'The query parameter \'' . $variable . '\' needs to be of type integer, but we found \'' . $input . '\''],
        ['error' => 'Invalid type', 'description' => 'The query parameter \'' . $variable . '\' needs to be of either \'desc\' or \'asc\', but we found \'' . $input . '\'']
    ];
    header('Content-Type: application/json');
    echo json_encode($errors[$type]);
}

function getURL(): string
{
    return getProtocol() . $_SERVER['SERVER_NAME'];
}

function getProtocol(): string
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return 'https://';
    }

    return 'http://';
}