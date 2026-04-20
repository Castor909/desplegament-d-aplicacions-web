# LDAP Practice Runbook

This file lists the exact action order to follow on the Ubuntu Server VM while documenting the work in `report.md`.

## Before starting

1. Open the VM console or SSH session from Arch Linux.
2. Confirm the VM is reachable.
3. Decide the LDAP domain name and keep it consistent everywhere.
4. Decide whether the bonus will be attempted.

## Task 1: install and enable LDAP

Run these commands on Ubuntu Server:

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y slapd ldap-utils
sudo systemctl enable slapd
sudo systemctl status slapd
sudo ufw allow ssh
sudo ufw allow 389/tcp
sudo ufw status verbose
```

What to capture:
- package update output
- package installation output
- `systemctl status slapd`
- firewall rules

## Task 2: configure Base DN and base tree

Typical actions:

```bash
sudo dpkg-reconfigure slapd
```

Then create a base LDIF file with:
- domain entry
- `ou=usuarios`
- `ou=grupos`

Example structure:

```ldif
dn: dc=example,dc=com
objectClass: top
objectClass: dcObject
objectClass: organization
o: example
dc: example

dn: ou=usuarios,dc=example,dc=com
objectClass: organizationalUnit
ou: usuarios

dn: ou=grupos,dc=example,dc=com
objectClass: organizationalUnit
ou: grupos
```

Import and verify:

```bash
ldapadd -x -D "cn=admin,dc=example,dc=com" -W -f base.ldif
ldapsearch -x -LLL -b "dc=example,dc=com"
```

## Task 3: create users and group

Create at least three users and one group.

Useful commands:

```bash
ldapadd -x -D "cn=admin,dc=example,dc=com" -W -f users.ldif
ldapadd -x -D "cn=admin,dc=example,dc=com" -W -f group.ldif
ldapsearch -x -LLL -b "ou=usuarios,dc=example,dc=com" "(uid=user1)"
ldapsearch -x -LLL -b "ou=usuarios,dc=example,dc=com" "(cn=User Name)"
```

Minimum attributes for each user:
- `cn`
- `sn`
- `uid`
- `mail`
- `userPassword`

## Task 4: protect Apache content with LDAP

Suggested flow:

```bash
sudo a2enmod ldap authnz_ldap
sudo systemctl restart apache2
sudo systemctl status apache2
```

Example Apache block:

```apache
<Directory /var/www/html/privado>
    AuthType Basic
    AuthName "Acceso privado"
    AuthBasicProvider ldap
    AuthLDAPURL "ldap://SERVER_IP:389/ou=usuarios,dc=example,dc=com?uid"
    Require valid-user
</Directory>
```

Test from browser:
- access without credentials must fail
- access with LDAP user must work

## Task 5: client verification and capture

Useful checks:

```bash
ldapwhoami -x -D "uid=user1,ou=usuarios,dc=example,dc=com" -W
ldapsearch -x -LLL -b "dc=example,dc=com"
```

Capture traffic with either:
- Wireshark
- tcpdump

Example tcpdump:

```bash
sudo tcpdump -i any port 389 -n
```

## Bonus

Optional only if stable:
- StartTLS or LDAPS
- phpLDAPadmin

## After each step

1. Paste the command into `report.md`.
2. Add the result.
3. Add a screenshot or terminal capture reference.
4. Add one short explanation sentence.
