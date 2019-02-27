{
   "pdo_db": "sqlite:db/sqlite3.db"
  ,"email":{
    "to": "scott.holland@rgbsolutions.com.au"
  }
  ,"cache":{
    "dir": "/tmp/dis"
    ,"timeout": 7200
  }
  ,"whois":{
    "remoteExec":	"/usr/local/whois/query.php %s"
    ,"sshFullPath":	"/usr/bin/ssh"
    ,"delaySeconds": 12
  }
  ,"logfile":{
    "debug":	"log/info.log"
    ,"info":	"log/info.log"
    ,"warn":	"log/info.log"
    ,"error":	"log/info.log"
  }
  
  ,"mail":{
     "from":		"Domain Information System <zsombor@dis.rgbsolutions.com.au>"
    ,"returnPath":	"kalo@zsombor.net"
    ,"unsubscribe":	"http://dis.zsombor.net/unsubscribe.html?%s"
  }
  
}
