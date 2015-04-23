<?
/**
 * Bericode File Caching Class
 * Быстрое и динамическое кэширование на файлах.
 */

class Cache
{

    protected $_cache_path;


    public function __construct($cache_path = '')
    {
        if (is_dir($cache_path)) {
            $this->_cache_path = $cache_path;
        } else {

            exit("($cache_path) not folder!");

        }
    }


    /**
     * Получить данные кэша
     *
     * @param	строка	$id	кэш ID
     * @return	смешанные данные при успехе, FALSE при неудаче
     */
    public function get($id)
    {
        $data = $this->_get($id);
        return is_array($data) ? $data['data'] : false;
    }


    /**
     * Соханить в кеш
     *
     * @param	string	$id	кэша ID
     * @param	$data	данные
     * @param	int	$time_live	Время жизни в секундах
     * @return	bool TRUE в случае успеха, FALSE при неудаче
     */
    public function save($id, $data, $time_live = 60)
    {
        $contents = array(
            'time' => time(),
            'time_live' => $time_live,
            'data' => $data);


        if ($this->write_file($this->_cache_path . $id, serialize($contents))) {
            chmod($this->_cache_path . $id, 0640);
            return true;
        }

        return false;
    }


    /**
     * Удалить из кэша
     *
     *param  $id уникальный идентификатор элемента в кэше
     *return BOOL TRUE в случае успеха / FALSE при неудаче
     */
    public function delete($id)
    {
        return file_exists($this->_cache_path . $id) ? unlink($this->_cache_path . $id) : false;
    }


    /**
     * Очистить  кэш
     *
     * @return	BOOL TRUE в случае успеха / FALSE при неудаче
     */
    public function clean()
    {
        return $this->delete_files($this->_cache_path, false, true);
    }


    /**
     * Получить кэш метаданных
     *
     * @param	mixed	уникальный идентификатор элемента в кэше 
     * @return	mixed	при неудаче, масив в случае успеха.
     */
    protected function _get($id)
    {
        if (!file_exists($this->_cache_path . $id)) {
            return false;

        }

        $data = unserialize(file_get_contents($this->_cache_path . $id));

        if ($data['time_live'] > 0 && time() > $data['time'] + $data['time_live']) {
            unlink($this->_cache_path . $id);
            return false;
        }

        return $data;
    }

	
    /**
     * Удалить файлы
     *
     * Удалить все файлы в директории.
     * Файлы должны быть доступны для записи.
     * If the second parameter is set to TRUE, any directories contained
     * within the supplied base directory will be nuked as well.
     *
     * @param	string	$path		Путь к файлу
     * @param	bool	$del_dir	Удалять все каталоги
     * @param	bool	$htdocs		Пропустить удаление .htaccess и индексные страницы
     * @param	int	$_level		    Текущий уровень глубина директории (default: 0; только для внутреннего использования)
     * @return	bool
     */
    private function delete_files($path, $del_dir = false, $htdocs = false, $_level =
        0)
    {
        // Trim the trailing slash
        $path = rtrim($path, '/\\');

        if (!$current_dir = @opendir($path)) {
            return false;
        }

        while (false !== ($filename = @readdir($current_dir))) {
            if ($filename !== '.' && $filename !== '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $filename) && $filename[0] !== '.') {
                    delete_files($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $htdocs, $_level +
                        1);
                } elseif ($htdocs !== true or !preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i',
                $filename)) {
                    @unlink($path . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }

        closedir($current_dir);

        return ($del_dir === true && $_level > 0) ? @rmdir($path) : true;
    }


    /**
     * Записать файл
     *
     * Записывает данные в файл, указанный в пути.
     * Создает новый файл, если не существует.
     *
     * @param	string	$path	Путь к файлу
     * @param	string	$data	Данные для записи
     * @param	string	$mode	fopen() режим (поумолчанию: 'wb')
     * @return	bool
     */
    private function write_file($path, $data, $mode = 'wb')
    {
        if (!$fp = @fopen($path, $mode)) {
            return false;
        }

        flock($fp, LOCK_EX);

        for ($result = $written = 0, $length = strlen($data); $written < $length; $written +=
            $result) {
            if (($result = fwrite($fp, substr($data, $written))) === false) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }
}
?>
