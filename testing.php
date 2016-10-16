<?php

function arrayGet($array, $key, $throwOnMissing = false, $checkForPresenceOnly = false)
{
    // this seems like an odd case :/
    if(is_null($key)) {
        return $checkForPresenceOnly ? true : $array;
    }

    foreach(explode('.', $key) as $segment) {

        if(is_object($array)) {
            if(!property_exists($array, $segment)) {
                if($throwOnMissing) {
                    throw new \Exception(sprintf('Cannot find the key "%s"', $key));
                }

                // if we're checking for presence, return false - does not exist
                return $checkForPresenceOnly ? false : null;
            }
            $array = $array->{$segment};

        } elseif(is_array($array)) {
            if(!array_key_exists($segment, $array)) {
                if($throwOnMissing) {
                    throw new \Exception(sprintf('Cannot find the key "%s"', $key));
                }

                // if we're checking for presence, return false - does not exist
                return $checkForPresenceOnly ? false : null;
            }
            $array = $array[$segment];
        }
    }

    // if we're checking for presence, return true - *does* exist
    return $checkForPresenceOnly ? true : $array;
}

$array = ['users' => [
    ['nickname' => 'yaeger'],
    ['nickname' => 'sintax1']
]
];

var_dump(arrayGet($array, 'users.0.nickname'));