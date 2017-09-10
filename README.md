# drupal-org-issue-patch-history

Command line tool to generate branch of a project that contains the patch history of an issue (more or less).

## Installation

1. Clone this repository
2. `composer install`
3. Run './drupal-org-issue-patch-history` to see the help of the command line tool.

## Usage

```
./drupal-org-issue-patch-history create-branch <project> <issue> [<directory>] [<sourceBranch>] [<targetBranch>]
```

```
Arguments:
  project               The drupal org project machine name.
  issue                 The id of the issue to parse.
  directory             The directory of the repository to work with or to clone to. Defaults to project name.
  sourceBranch          Branch name to apply patches on [default: "8.x-1.x"]
  targetBranch          Branch name to create for the given issue. Defaults to issue-[issueID].

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  This command parses the issue history of a drupal.org issue and tries to build a representation of the patches within this history in a single branch.
```
