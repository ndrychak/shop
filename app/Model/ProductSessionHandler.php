<?php
class ProductSessionHandler
{
    private $savePath;
    private $db;

    public function __construct() {
        // Instantiate new Database object
        $this->db = Model::open_database_connection();

        session_set_save_handler(
            array($this, '_open'),
            array($this, '_close'),
            array($this, '_read'),
            array($this, '_write'),
            array($this, '_destroy'),
            array($this, '_gc')
        );

        register_shutdown_function('session_write_close');
        session_start();
    }

    public function _open()
    {
        if ($this->db) {
            return true;
        }
        return false;
    }

    public function _close()
    {
        if($this->db->close()){
            return true;
        }
        return false;
    }

    public function _read($id)
    {
        $dataTable = array();
        $session = $this->db->query('SELECT item_1, item_2 FROM session WHERE id=' . $id);
        if ($session) {
            while ($row = $session->fetch_assoc()) {
                $dataTable['item_1'] = $row['item_1'];
                $dataTable['item_2'] = $row['item_2'];
            }
            return $dataTable;
        } else {
            $this->db->query('INSERT INTO session (id, item_1, item_2) VALUES ("' . $id . '", 0, 0)');
            return '';
        }
    }

    public function _write($id, $data){
        $routes = explode('/', $_SERVER['REQUEST_URI']);

        if ($routes[1] == 'products' && isset($routes[3]) && is_numeric($routes[3])) {
            $itemID = $routes[3];

            $watched = 0;
            $isWatched = $this->db->query('SELECT watched FROM product WHERE id=' . $itemID);
            while ($watchedInfo = $isWatched->fetch_assoc()) {
                $watched = $watchedInfo['watched'];
            }
            $watched++;

            $this->db->query('UPDATE product SET watched = ' . $watched . ' WHERE id = ' . $itemID);

            /* recommendations */
            $dataTable = array();
            if ($result = $this->db->query('SELECT item_1, item_2 FROM session WHERE id=' . $id)) {
                while ($row = $result->fetch_assoc()) {
                    $dataTable['item_1'] = $row['item_1'];
                    $dataTable['item_2'] = $row['item_2'];
                }
            }
        }

        return false;
    }

    public function _destroy($id)
    {
        return true;
    }

    public function _gc($maxlifetime)
    {
        return true;
    }
}