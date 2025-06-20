<?php

namespace App\Services;

use App\Models\LDAP\user as ldapUser;
use Illuminate\Auth\AuthenticationException;

class LdapService
{
  protected $ldap;

  public function __construct()
  {
    $this->ldap = ldap_connect(config('app.ldap_conn'));
    ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, 0);
  }

  public function bind(string $samAccountName, string $password)
  {
    $us_ldap_logon = config('database.ldap.ldap_domain_prefix') . "\\" . $samAccountName;

    $bind = @ldap_bind($this->ldap, $us_ldap_logon, $password);

    if (!$bind) {
      $errorCode = ldap_errno($this->ldap);
      $errorMessage = ldap_error($this->ldap);

      if ($errorCode === 49) {
        throw new AuthenticationException('Verkeerde inloggegevens');
      }

      throw new \Exception("LDAP bind failed: [$errorCode] $errorMessage");
    }

    return $bind;
  }

  public function getUserByUsername($samAccountName)
  {
    $filter = "(sAMAccountName=" . $samAccountName . ")";
    $result = ldap_search($this->ldap, 'dc=' .  config('database.ldap.ldap_dn1') . ',dc=' . config('database.ldap.ldap_dn2'), $filter);
    $entries = ldap_get_entries($this->ldap, $result);

    if (isset($entries[0])) {
      $filter = "(sAMAccountName=" . ($impersonated ?? $samAccountName) . ")";
      $result = ldap_search($this->ldap, 'dc=' .  config('database.ldap.ldap_dn1') . ',dc=' . config('database.ldap.ldap_dn2'), $filter);
      $entries = ldap_get_entries($this->ldap, $result);

      if (isset($entries[0])) {
        $entries = $entries[0];

        $data = [
          'objectsid' => $this->sidToString($entries['objectsid'][0]),
          'firstname' => isset($entries['givenname']) ? ucfirst($entries['givenname'][0]) : '',
          'lastname' => isset($entries['sn']) ? ucfirst($entries['sn'][0]) : '',
          'username' => $entries['samaccountname'][0],
          'email' => $entries['mail'][0] ?? null,
          'memberof' => $entries['memberof'],
        ];

        $user = new ldapUser();
        return $user->setAttributes($data);
      }
    }

    throw new AuthenticationException('De geïmpersonificeerde gebruiker bestaat niet in Active Directory.');
  }

  protected function closeBind($bind)
  {
    @ldap_close($bind);
  }

  // OBJECTSID (AD) TO STRING
  private function sidToString($ADsid)
  {
    $sid = "S-";
    $sidinhex = str_split(bin2hex($ADsid), 2);
    // Byte 0 = Revision Level
    $sid = $sid . hexdec($sidinhex[0]) . "-";
    // Byte 1-7 = 48 Bit Authority
    $sid = $sid . hexdec($sidinhex[6] . $sidinhex[5] . $sidinhex[4] . $sidinhex[3] . $sidinhex[2] . $sidinhex[1]);
    // Byte 8 count of sub authorities - Get number of sub-authorities
    $subauths = hexdec($sidinhex[7]);
    //Loop through Sub Authorities
    for ($i = 0; $i < $subauths; $i++) {
      $start = 8 + (4 * $i);
      // X amount of 32Bit (4 Byte) Sub Authorities
      $sid = $sid . "-" . hexdec($sidinhex[$start + 3] . $sidinhex[$start + 2] . $sidinhex[$start + 1] . $sidinhex[$start]);
    }
    return $sid;
  }
}
