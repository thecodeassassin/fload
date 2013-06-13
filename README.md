fload
=====

File upload script based on HTTP PUT


Easy to use:
curl -T yourfile http://domain.com

or
cat yourfile | curl -T http://domain.com

or
echo 'test paste' | curl -T http://domain.com

or
etc.

Output is metadata, URL, and more stuff in JSON.
