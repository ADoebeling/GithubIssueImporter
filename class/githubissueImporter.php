<?php
/**
 * GithubIssueImporter
 * Imports mails from imap-account ans rss-feeds as github.com-issue
 *
 * @Author      Andreas Doebeling <ad@1601.com>
 * @Copyright   1601.production siegler&thuemmler ohg
 * @License     cc-by-sa - http://creativecommons.org/licenses/by-sa/4.0/
 * @Link        http://www.1601.com
 * @Link        http://xing.doebeling.de
 */



namespace SNE\githubIssueImporter;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class githubIssueImporter {

    protected $mailAccount;
    
    protected $mails = array();
    
    protected $feedAccount; 
    
    protected $feed = array();
    
    protected $githubAccount;
 
    protected $issues = array();

    

    public function __construct()
    {
        $_GlOBALS['log'] = new Logger('mail2github'):


    }


    public function setMailAccount($host, $user, $pwd, $type="IMAP")
    {

    }
    
    public function setRssAccount($url, $user, $pwd, $type="ATOM")
    {

    }

    public function setGithubAccount ($user, $pwd, $repo)
    {

    }

    public function loadAll()
    {
        $this->loadMails();
        $this->loadRss();
        return $this;
    }
    
    public function loadMails($parser = 'PLAIN')
    {
        return $this;
    }
    
    public function loadFeed($parser = 'RSS')
    {
        return $this;
    }




    public function postIssues()
    {
        foreach ($this->issues as &$issue)
        {
            $issue->post();
        }
    }

}


// Example Usage

$m2g = new mail2github();

$m2g -> setEmailAccount('imap.domain.tld', 'IMAP')
     -> setGithubAccount('GitBot', '12345', 'Git-Inbox')
     -> fetchMails()
     -> makeIssues('diconn.de')
     -> postIssues();
