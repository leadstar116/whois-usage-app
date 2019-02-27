CREATE TABLE tblAssistant(ID INTEGER PRIMARY KEY, Hostname text not null, UserName text not null);
CREATE TABLE tblMXRecords (ID INTEGER PRIMARY KEY, MXRecord TEXT);
CREATE TABLE tblManagedMailDomains(ID integer primary key, Domain text, StoreDateTime datetime default CURRENT_TIMESTAMP, is_google boolean, is_ms boolean);
CREATE TABLE tblNameservers (ID INTEGER PRIMARY KEY, Nameservers TEXT);
CREATE TABLE tblTargetCompany (ID INTEGER PRIMARY KEY, Name TEXT);
CREATE TABLE tblTemplate(ID integer primary key, name text, subject text, body text, storeDatetime datetime default CURRENT_TIMESTAMP);
CREATE TABLE "tblTemplateSent" (
        `templateID`    integer NOT NULL,
        `domainID`      integer NOT NULL, sentTime datetime default CURRENT_TIMESTAMP, PRIMARY KEY(templateID,domainID)
);
CREATE TABLE "tblUnprocessedDomains" (
	`ID`	INTEGER,
	`Domain`	TEXT,
	`Registrant`	TEXT,
	`EligibilityID`	TEXT,
	`RegistrantContactName`	TEXT,
	`RegistrantContactEmail`	TEXT,
	`Phone`	TEXT,
	`TechName`	TEXT,
	`TechEmail`	TEXT,
	`MXID`	integer,
	`NameserverID`	integer,
	`TargetCompanyID`	integer,
	`StoreDateTime`	datetime DEFAULT CURRENT_TIMESTAMP,
	`ProcessedDateTime`	datetime,
	`is_google`	boolean,
	`is_ms`	boolean,
	`TechPhone`	text,
	`Changed`	datetime,
	`Expiry`	date,
	`NotExists`	smallint DEFAULT 0,
	`RespectiveURL`	text,
	`Discarded`	datetime,
	`Unsubscribed`	datetime,
	PRIMARY KEY(ID)
);
CREATE TABLE tblUser(ID integer primry key, createdDatetime datetime default CURRENT_TIMESTAMP, email text, password char(64) );
CREATE TABLE tblWhoisServer(ID integer primary key, Hostname text not null, dailyLimit int default 99, tld text not null);
CREATE TABLE tblWhoisServerQuery(ID INTEGER PRIMARY KEY, whoisServer integer, queryTimestamp timestamp default CURRENT_TIMESTAMP, Assistant int);
CREATE VIEW v_pending as select ID, Domain, StoreDateTime from tblUnprocessedDomains where ProcessedDateTime is null;
CREATE VIEW v_domain as select d.*, mx.MXRecord, ns.Nameservers, c.Name as Company from tblUnprocessedDomains d left join tblMXRecords mx on d.MXID=mx.ID left join tblNameservers ns on d.NameserverID=ns.ID left join tblTargetCompany c on d.TargetCompanyID=c.ID;
CREATE VIEW v_discarded as select * from v_domain where not discarded is null or not unsubscribed is null;
CREATE VIEW v_prospects as select d.* 
from v_domain d
left join tblTemplateSEnt s on d.id=s.domainID
where not MXID is null and not ProcessedDatetime is null and NotExists<>1 
and discarded is null and unsubscribed is null  and s.domainID is null;
CREATE VIEW v_contacted as select d.* , s.sentTime as Contacted, t.name as Template
from v_domain d
left join tblTemplateSEnt s on d.id=s.domainID
left join tblTemplate t on s.templateID=t.ID
where not MXID is null and not ProcessedDatetime is null and NotExists<>1 
and discarded is null and unsubscribed is null  and s.domainID is not null;
CREATE UNIQUE INDEX tblUnprocessedDomains_uix on tblUnprocessedDomains(Domain);
