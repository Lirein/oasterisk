<?php

namespace scheduler;

class InternalTrigger extends Trigger {

  public static function getName() {
    return "Триггер по умолчанию";
  }

  public function start($variables) {   
    return (object)array('variables' => $variables);
  }

  public function trigger($request_data) {
    return false;
  }
}

?>