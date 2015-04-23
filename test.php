<? 
 
 require_once('cache.php');
 $cache = new cache(); 

 if ( ! $data = $cache->get('test'))
 {
      echo 'Saving to the cache!<br />';
      $data = 'Test cache.';

      // Сохранить на 5 секунд
      $cache->save('test', $data, 5);
  } 
  
   echo $data;
   
?>
