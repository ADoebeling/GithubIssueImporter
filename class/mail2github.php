<?php
/**
 * mail2github
 * Imports mails from imap-account as github.com-issue
 *
 * @Author      Andreas Doebeling <ad@1601.com>
 * @Copyright   1601.production siegler&thuemmler ohg
 * @License     cc-by-sa - http://creativecommons.org/licenses/by-sa/4.0/
 * @Link        http://www.1601.com
 * @Link        http://xing.doebeling.de
 */



namespace SNE\mail2github;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class mail2github {


    protected $mails = array();
    protected $issues = array();

    protected $mailAccount;
    protected $githubAccount;

    public function __construct()
    {
        $_GlOBALS['log'] = new Logger('mail2github'):


    }


    public function setMailAccount($host, $user, $pwd, $type="IMAP")
    {

    }

    public function setGithubAccount ($user, $pwd, $repo)
    {

    }

    public function fetchMails()
    {

    }


    public function makeIssues ($parser = 'plain')
    {

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