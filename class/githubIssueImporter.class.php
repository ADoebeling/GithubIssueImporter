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



//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;
use PhpImap\Exception;

require_once '../class/imap/Mailbox.php';
require_once '../class/imap/IncomingMail.php';
require_once '../class/github/client/GitHubClient.php';

class githubIssueImporter {

    protected $mailbox;
    
    protected $mails = array();
    
    protected $feedAccount; 
    
    protected $feed = array();
    
    protected $githubRepo;
 
    protected $issues = array();

    

    public function __construct()
    {
        //$_GlOBALS['log'] = new Logger('mail2github'):
    }


    public function setMailbox($host, $user, $pwd, $type="IMAPs")
    {
        if ($type == 'IMAPs')
        {
            $this->mailbox = new PhpImap\Mailbox('{'.$host.':993/imap/ssl}INBOX', $user, $pwd, __DIR__);
        }
        else
        {
            throw new \Exception ('Protocol "$type" not implemented yet', 501);
        }
        return $this;
    }
    
    public function setRssAccount($url, $user, $pwd, $type="ATOM")
    {
        return $this;
    }

    public function setGithubAccount ($user, $pwd)
    {
        $this->githubRepo = new GitHubClient();
        $this->githubRepo->setCredentials($user, $pwd);
        return $this;
    }

    public function loadAll()
    {
        $this->loadMails();
        $this->loadRss();
        return $this;
    }
    
    public function loadMails()
    {
        $mailsIds = $this->mailbox->searchMailBox('ALL');
        if(!$mailsIds) {
            // empty
        }

        foreach ($mailsIds as $id)
        {
            $this->mails[$id] = $this->mailbox->getMail($id);
        }

        return $this;
    }
    
    public function loadFeed($parser = 'RSS')
    {
        return $this;
    }


    public function buildIssues($source = 'mail', $parser = 'plain')
    {
        if ($source == 'mail' && $parser == 'plain')
        {
            foreach ($this->mails as $mailId => &$mail)
            {
                $issue['title'] =   utf8_decode($mail->subject).' am '.$mail->date;
                $issue['text'] =    utf8_decode($mail->textPlain);
                $this->issues[] = $issue;
            }
        }
        elseif ($source == 'mail' && $parser == 'diconn')
        {
            foreach ($this->mails as $mailId => &$mail)
            {
                $issue['title'] =   utf8_decode($mail->subject).' am '.$mail->date;
                $issue['text'] =    utf8_decode($mail->textPlain);
                $issue['label'] =   array('Support', 'Call');
                $this->issues[] = $issue;
            }
        }
        else
        {
            throw new \Exception ("Source '$source' with parser '$parser' not implemented (yet)", 501);
        }
        return $this;
    }

    public function postIssues($repoOwner, $repo, $assignee)
    {
        foreach ($this->issues as &$issue)
        {
            $this->githubRepo->issues->createAnIssue($repoOwner, $repo, $issue['title'], utf8_encode($issue['text']), $assignee);
            echo $issue['text'];
        }

        //print_r($this->issues);

        return $this;
    }

}