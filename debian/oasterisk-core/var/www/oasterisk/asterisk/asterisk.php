<?php

/**
 * Возвращает отпечаток существующего файла
 *
 * @param string $filename Имя файла веб-сервера
 * @return string Имя файла с отпечатком
 */
function fingerprint(string $filename) {
  $result = $filename;
  if('/' == $filename[0]) {
    $afilename = $_SERVER['DOCUMENT_ROOT'].$filename;
  } else {
    $afilename = $_SERVER['DOCUMENT_ROOT'].'/'.$filename;
  }
  if(file_exists($afilename)) {
    $fingerprint = date('YmdHis', fileatime($afilename));
    $result = substr($filename, 0, strrpos($filename, '.')).'%23'.$fingerprint.substr($filename, strrpos($filename, '.'));
  }
  return $result;
}

/**
 * Возвращает разницу между двумя датами в секундах.
 *
 * @param \DateTime $datea Первая дата
 * @param \DateTime $dateb Вторая дата
 * @return integer Разница между датами в секундах
 */
function getDateDiff(&$datea, &$dateb) {
  return abs($dateb->getTimestamp() - $datea->getTimestamp());
}

function object_merge(stdClass $objecta, stdClass $objectb) {
  return (object) array_merge_recursive((array) $objecta, (array) $objectb);
}

?>
