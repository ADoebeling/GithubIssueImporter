<?php
require_once '../class/githubIssueImporter.class.php';
$gii        = new githubIssueImporter();
$gii        -> setGithubAccount('gitHub-User', 'API-Token') // need (gh-user, password)
            -> setMailbox('imapsServer.tld', 'you@mail.com', '123456') //need (server, mailadress, password)
            -> loadMails()
            -> buildIssues('mail', 'diconn')
            -> setAssigneeByKeyword('title','user1') 
            -> postIssues('gitHub-RepoOwner', 'gitHub-Repo', null, array('Support', 'Call')) //need: (repo-owner, repo)

            // If you want to import multiple accounts
            -> reset();