<?php
switch($section[1])
{
    case 'atlas':
        switch($section[2])
        {
            case 'v1':
                switch($section[3])
                {
                    case 'news':
                        require_once __DIR__ . '/../../backend/atlas/v1/classes/News.php';
                        $data = new News();
                        switch($section[4])
                        {
                            case '':
                                header('Content-Type: application/json');
                                if (isset($_GET['order'])) {
                                    if ('desc' === $_GET['order'] || 'asc' === $_GET['order']) {
                                        echo json_encode($data->getJSONFromSQL($data->getItems($_GET['order']), false, $_GET));
                                    }
                                } else {
                                    echo json_encode($data->getJSONFromSQL($data->getItems(), false, $_GET));
                                }
                                break;

                            case ((bool)preg_match('/^\d+$/', $section[4]) === true):
                                header('Content-Type: application/json');
                                echo json_encode($data->getJSONFromSQL($data->getResultByID($section[4]),false, $_GET));
                                break;

                            case 'latest':
                                header('Content-Type: application/json');
                                echo json_encode($data->getJSONFromSQL($data->getItems(),true, $_GET));
                                break;

                            default:
                                errorInvalid();
                                break;
                        }
                        break;

                    case 'releases':
                        require_once __DIR__ . '/../../backend/atlas/v1/classes/Releases.php';
                        $data = new Releases();
                        switch($section[4])
                        {
                            case '':
                                header('Content-Type: application/json');
                                if (isset($_GET['order'])) {
                                    if ('desc' === $_GET['order'] || 'asc' === $_GET['order']) {
                                        echo json_encode($data->getJSONFromSQL($data->getItems($_GET['order']), false, $_GET));
                                    }
                                } else {
                                    echo json_encode($data->getJSONFromSQL($data->getItems(), false, $_GET));
                                }
                                break;

                            case ((bool)preg_match('/^\d+$/', $section[4]) === true):
                                header('Content-Type: application/json');
                                echo json_encode($data->getJSONFromSQL($data->getResultbyId($section[4]),false, $_GET));
                                break;

                            case 'latest':
                                header('Content-Type: application/json');
                                $params = ['latest' => true];
                                echo json_encode($data->getJSONFromSQL($data->getItems(), true, $_GET));
                                break;

                            default:
                                errorInvalid();
                                break;
                        }
                        break;

                    case 'version':
                        require_once __DIR__ . '/../../backend/atlas/v1/classes/Version.php';
                        $data = new Version();
                        switch($section[4])
                        {
                            case '':
                                header('Content-Type: application/json');
                                echo json_encode($data->getJSONFromSQL());
                                break;

                            default:
                                errorInvalid();
                                break;
                        }
                        break;

                    default:
                        errorInvalid();
                        break;
                }
                break;

            default:
                errorInvalid();
                break;
        }
        break;

    default:
        include_once __DIR__ . '/../pages/frontpage.php';
        break;
}

function errorInvalid() {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid URL', 'description' => 'Invalid URL provided. Please refer to the documentation: ' . getURL() . '/docs/atlas/v1/']);
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