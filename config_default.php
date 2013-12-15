<?php
define('MONGO', 'mongodb:///tmp/mongodb-27017.sock');
define('DB', 'mycard');

date_default_timezone_set('UTC');

function document_output(&$document)
{
    if (is_array($document)) {
        if(array_diff_key($document,array_keys(array_keys($document)))){ //assoc
            foreach ($document as $key => $value) {
                if ($key == '_id') {
                    $document['id'] = (string)$value;
                    unset($document['_id']);
                } elseif (is_a($value, 'MongoId')) {
                    $document[$key] = (string)$value;
                } elseif (is_a($value, 'MongoDate')) {
                    $document[$key] = date(DateTime::W3C, $value->sec);
                } elseif (is_array($value)) {
                    document_output($value);
                    $document[$key] = $value;
                }
            }
        } else { //indexed array
            array_walk($document, 'document_output');
        }
    }
}

function document_join(&$array, $join)
{
    foreach($join as $key => $value){
        $sub_documents = iterator_to_array($value->find(array('_id' => array('$in' => array_column($array, $key)))));
        var_dump($sub_documents);
        foreach($array as &$document){
            $document = array_replace($sub_documents[(string)$document[$key]], $document);
            unset($document[$key]);
        }
    }
}

function document_embed(&$array, $join)
{
    foreach($join as $key => $value){
        $sub_documents = iterator_to_array($value->find(array('_id' => array('$in' => array_column($array, $key)))));
        foreach($array as &$document){
            $document[$key] = $sub_documents[(string)$document[$key]];
        }
    }
}