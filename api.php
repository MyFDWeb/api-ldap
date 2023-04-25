<?php

if ($_SERVER["REQUEST_URI"] == "/" || $_SERVER["REQUEST_METHOD"] == "GET") {
    header("Location: https://api.myfdweb.de/ldap");
    exit;
}

$headers = array_change_key_case(getallheaders());
$body = json_decode(file_get_contents("php://input"), true);
if ($body == null)
    $body = [];
ini_set("display_errors", "off");

if (getenv("ALLOW_ORIGINS") && in_array(parse_url($_SERVER['HTTP_REFERER'])["host"], explode(",", getenv("ALLOW_ORIGINS")))) {
    header("Access-Control-Allow-Origin: https://" . parse_url($_SERVER['HTTP_REFERER'])["host"]);
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Authorization,Content-Type,X-LDAP-URI");
}
header("Content-Type: application/json");

function error(string $msg): void
{
    global $ldap;
    echo(json_encode(["result" => "error", "message" => $msg]));
    if (isset($ldap))
        ldap_close($ldap);
    exit;
}

function endsWith($haystack, $needle): bool
{
    $length = strlen($needle);
    if (!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

function cleanUpEntry($entry): array
{
    $retEntry = array();
    for ($i = 0; $i < $entry['count']; $i++) {
        if (is_array($entry[$i])) {
            $subtree = $entry[$i];
            //This condition should be superfluous so just take the recursive call
            //adapted to your situation in order to increase perf.
            if (!empty($subtree['dn']) && !isset($retEntry[$subtree['dn']]))
                $retEntry[$subtree['dn']] = cleanUpEntry($subtree);
            else
                $retEntry[] = cleanUpEntry($subtree);
        } else {
            $attribute = $entry[$i];
            if ($entry[$attribute]['count'] == 1)
                $retEntry[$attribute] = $entry[$attribute][0];
            else
                for ($j = 0; $j < $entry[$attribute]['count']; $j++)
                    $retEntry[$attribute][] = $entry[$attribute][$j];
        }
    }
    return $retEntry;
}

if (!isset($headers["x-ldap-uri"]))
    error("Missing X-LDAP-URI Header.");
$ldap_uri = $headers["x-ldap-uri"];
$ldap = ldap_connect($ldap_uri);
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);


if (!isset($headers["authorization"]))
    error("Missing Authorization Header.");
if (explode(" ", $headers["authorization"])[0] != "Basic")
    error("Currently only Basic auth is supported.");
$auth = explode(":", base64_decode(explode(" ", $headers["authorization"])[1]));
if (!ldap_bind($ldap, $auth[0], $auth[1]))
    error("Could not bind to the ldap server using the given credentials.");

if (endsWith($_SERVER["REQUEST_URI"], "/whoami")) {
    echo(json_encode(["result" => "success", "entry" => ldap_exop_whoami($ldap)]));
} else if (endsWith($_SERVER["REQUEST_URI"], "/add")) {
    if (!isset($body["dn"]) || !isset($body["entry"]))
        error("Missing parameter.");
    if (!ldap_add($ldap, $body["dn"], $body["entry"]))
        error(ldap_error($ldap));
} else if (endsWith($_SERVER["REQUEST_URI"], "/delete")) {
    if (!isset($body["dn"]))
        error("Missing parameter.");
    if (!ldap_delete($ldap, $body["dn"]))
        error(ldap_error($ldap));
} else if (endsWith($_SERVER["REQUEST_URI"], "/modify")) {
    if (!isset($body["dn"]) || !isset($body["entry"]))
        error("Missing parameter.");
    if (!ldap_modify($ldap, $body["dn"], $body["entry"]))
        error(ldap_error($ldap));
} else if (endsWith($_SERVER["REQUEST_URI"], "/search")) {
    if (!isset($body["base"]) || !isset($body["filter"]) || !isset($body["attributes"]))
        error("Missing parameter.");
    if (!is_array($body["attributes"]))
        error("Parameter attributes must be of type array.");
    $result = ldap_search($ldap, $body["base"], $body["filter"], $body["attributes"]);
    if (!$result)
        error(ldap_error($ldap));
    echo(json_encode(["result" => "success", "entry" => cleanUpEntry(ldap_get_entries($ldap, $result))]));
} else if (endsWith($_SERVER["REQUEST_URI"], "/passwd")) {
    if (!isset($body["user"]) || !isset($body["password"]))
        error("Missing parameter.");
    if (!ldap_exop_passwd($ldap, $body["user"], "", $body["password"]))
        error(ldap_error($ldap));
} else if (endsWith($_SERVER["REQUEST_URI"], "/rename")) {
    if (!isset($body["dn"]) || !isset($body["new_dn"]) || !isset($body["new_parent"]))
        error("Missing parameter.");
    if (!ldap_rename($ldap, $body["dn"], $body["new_dn"], $body["new_parent"], true))
        error(ldap_error($ldap));
}
