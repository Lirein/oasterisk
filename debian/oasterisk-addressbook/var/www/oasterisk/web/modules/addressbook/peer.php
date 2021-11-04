<?php

namespace addressbook;

class AddressBookPeer extends \core\ChannelPeer {

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-addressbook');
    return $result;
  }

  public function getPeers() {
    $users = array();
    $addressBook = new \addressbook\AddressBook();
    $books = $addressBook->getBooks();
    foreach(array_keys($books) as $book) {
      $contacts = $addressBook->getContacts($book);
      foreach($contacts as $contact) {
        $peer = new \stdClass();
        $peer->type = 'Local';
        $peer->link = 'Addressbook';
        $peer->ip = NULL;
        $peer->name = $contact->name;
        $peer->mode = 'peer';
        $peer->number = $contact->id;
        $peer->status = 'OK';
        $peer->login = $contact->id.'@ab-'.$book;
        $users[] = $peer;
      }
    }
    return $users;
  }

}

?>
