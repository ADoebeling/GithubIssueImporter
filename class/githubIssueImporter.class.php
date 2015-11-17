<?php
/**
 * GithubIssueImporter
 * Imports mails from imap-account ans rss-feeds as github.com-issue
 * Parses some special mails like diconn.de-call-notifications
 *
 * @Author      Andreas Doebeling <ad@1601.com>
 * @Copyright   1601.production siegler&thuemmler ohg
 * @License     cc-by-sa - http://creativecommons.org/licenses/by-sa/4.0/
 * @Link        http://www.1601.com
 * @Link        http://xing.doebeling.de
 */


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

    public function reset()
    {
        unset($this->mailbox, $this->feedAccount, $this->githubRepo);
        $this->mails = array();
        $this->feed = array();
        $this->issues = array();

        return $this;
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
    
    public function loadMails($delete=true)
    {
        $mailsIds = $this->mailbox->searchMailBox('ALL');
        if(!$mailsIds) {
            // empty
        }

        foreach ($mailsIds as $id)
        {
            $this->mails[$id] = $this->mailbox->getMail($id);
            if ($delete)
            {
                $this->mailbox->deleteMail($id);
            }
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
                $issue['title'] =   utf8_decode($mail->subject);
                $issue['text'] =    utf8_decode($mail->textPlain);
                $this->issues[] = $issue;
            }
        }
        elseif ($source == 'mail' && $parser == 'diconn')
        {
            foreach ($this->mails as $mailId => &$mail)
            {
                $mail->textPlain = str_replace("Wir haben für Sie folgenden Anruf angenommen:\r\n", '', $mail->textPlain);
                $mail->textPlain = str_replace("Herzliche Grüße\nIhr DiConn Team", '', $mail->textPlain);
                $mail->textPlain = str_replace("diconn", '', $mail->textPlain);

                // Notiz extrahieren
                $notiz = explode("Notiz: ", utf8_decode($mail->textPlain));
                $notiz = explode('Telefon: ', $notiz[1]);
                $notiz = explode('Mobil: ', $notiz[0]);
                $notiz = explode('E-Mail: ', $notiz[0]);
                $notiz = explode('Bearbeitet durch: ', $notiz[0])[0];
				
                var_dump($notiz);
                
                // Meta-Angaben zur MD-Tabelle aufbereiten
                $text = explode("\r\n", utf8_decode($mail->textPlain));
                $i = 0;
                foreach ($text as &$line)
                {
                    $i++;
                    $delimiter = ':';
                    $element = explode($delimiter, $line, 2);

                    if (isset($element[1]) && !empty(trim($element[1])) && $element[0] != "Notiz") {
                        $line = "{$element[0]} | {$element[1]}";
                        if ($i == 1) $line .= " Uhr \n-------|-------";
                        $line .= "\n";
                    }
                    else
                    {
                        $line = '';
                    }
                }
                $text = implode("", $text);
                $text .= "\n$notiz";

                $issue['title'] =   utf8_decode($mail->subject);
                $issue['text'] =    $text;
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

    public function postIssues($repoOwner, $repo, $assignee = NULL, $label)
    {
        foreach ($this->issues as &$issue)
        {
            echo "this->githubRepo->issues->createAnIssue($repoOwner, $repo, {$issue['title']}, {$issue['text']}, $assignee, NULL, {$label})\n";
            $this->githubRepo->issues->createAnIssue($repoOwner, $repo, utf8_encode($issue['title']), utf8_encode($issue['text']), $assignee, NULL, $label);
        }
        return $this;
    }
}