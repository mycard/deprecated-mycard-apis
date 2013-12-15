<?php
class MyCard
{
    const KEY = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=';

    static function decode($cards_encoded) {
        $result = array();
        for ($i = 0; $i < strlen($cards_encoded); $i += 5) {
            $decoded = 0;
            foreach (str_split(substr($cards_encoded, $i, 5)) as $char) {
                $decoded = ($decoded << 6) + strpos(MyCard::KEY, $char);
            }
            $card_id = $decoded & 0x07FFFFFF;
            $side = $decoded >> 29;
            $count = $decoded >> 27 & 0x3;
            $result[] = array(
                'card_id' => $card_id,
                'side' => (bool)$side,
                'count' => $count
            );
        }
        return $result;
    }
}