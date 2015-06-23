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

    protected $feed;
    
    protected $githubRepo;

    /**
     * Array with all prepared issues: $issues[] = array('title' => $title, 'text' => $text, 'assignee' => $assignee)
     * @var array
     */
    protected $issues = array();

    

    public function __construct()
    {
        //$_GlOBALS['log'] = new Logger('mail2github'):
        $this->githubRepo = new GitHubClient();
    }

    public function reset()
    {
        unset($this->mailbox, $this->githubRepo, $this->feed);
        $this->mails = array();
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
    
    public function loadFeed($url)
    {
        $this->feed = simplexml_load_file($url);
        return $this;
    }

    public function setGithubAccount ($user, $pwd)
    {
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


    public function buildIssues($source, $parser = 'plain')
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

                $issue['title'] =   utf8_encode($mail->subject);
                $issue['text'] =    utf8_encode($text);
                $issue['label'] =   array('Support', 'Call');
                $this->issues[] = $issue;
            }
        }
        elseif ($source == 'rss' && $parser == 'status.df.eu')
        {
            foreach($this->feed->item as $item)
            {
                //$guid = (int)explode('#', $item->link)[1];
                $issue['title'] = $item->title;
                $issue['text'] = $item->description."\n_____\n".$this->feed->channel->title."\n".$this->feed->channel->description."\n".$item->link;

                $this->issues[] = $issue;
            }

            print_r($this->issues);
        }
        else
        {
            throw new \Exception ("Source '$source' with parser '$parser' not implemented (yet)", 501);
        }

        print_r($this->issues); die();
        return $this;
    }

    /**
     * Assign issue to users by keyword
     *
     * @since v1.1
     * @link https://github.com/ADoebeling/GithubIssueImporter/issues/6
     *
     * @param $haystack
     * @param $needle
     * @param $assignee
     * @return $this
     * @throws Exception 501 if $haystack is not title or text
     */
    public function setAssigneeByKeyword($haystack, $needle, $assignee)
    {
        if ($haystack != 'title' && $haystack != 'text')
        {
            throw new Exception("\$haystack '$haystack' not specified", 501);
        }
        foreach ($this->issues as &$issue)
        {
            if (strpos($issue[$haystack], $needle) !== false)
            {
                $issue['assignee'] = $assignee;
            }
        }
        return $this;
    }

    public function postIssues($repoOwner, $repo, $defaultAssignee = NULL, $label, $update = false)
    {
        foreach ($this->issues as &$issue)
        {
            if (!isset($issue['assignee']))
            {
                $issue['assignee'] = $defaultAssignee;
            }

            if ($update)
            {
                $currentIssues = $this->githubRepo->issues->listIssues($repoOwner, $repo);
                var_dump($currentIssues);
                die();
            }
            else
            {
                echo "this->githubRepo->issues->createAnIssue($repoOwner, $repo, {$issue['title']}, {$issue['text']}, $assignee, NULL, {$label})\n";
                $this->githubRepo->issues->createAnIssue($repoOwner, $repo, $issue['title'], $issue['text'], $issue['assignee'], NULL, $label);
            }
        }
        return $this;
    }
}