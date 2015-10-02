<?php

require_once '../config/config.inc.php';
require_once '../class/githubIssueImporter.class.php';


$gii        = new githubIssueImporter();
$gii        -> setGithubAccount('gitHub-User', 'API-Token')

            -> setMailbox('imapsServer.tld', 'you@mail.com', '123456')
            -> loadMails()
            -> buildIssues('mail', 'diconn')

            -> postIssues('gitHub-RepoOwner', 'gitHub-Repo', 'Assingee', array('Support', 'Call'))

            // If you want to import multiple accounts
            -> reset();