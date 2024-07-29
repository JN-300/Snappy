# Snappy
This is a simple TYPO3 extension to create a snapshot from a given page.


## Install

### composer
```console 
## add github repo to composer
$ composer config repositories.snappy vcs https://github.com/JN-300/Snappy
$ composer require jene/snappy 
```

### update database
### if helhum/typo3-console installed, just
```console
$ ddev typo3 database:updateschema
```
### else
> go to admin tools -> Maintenance -> Analyze Database Structure

__________________________________________________________________________________________________________________ 

## Usage
- in page and list view you will find a new dropdown in the uppper right corner from where you can create a snapshot or restore one
- also you will find an extra module called snappy in the left toolbar to manage your snapshots

__________________________________________________________________________________________________________________

## Extend
There are two Events, in which you can hook.
1. SnapshotAfterLoadingPageDataEvent
2. SnapshotAfterRestoringPageDataEvent

The first event will dispatch after collecting the page data and before storing them into the database
The second event will dispatch after receiving the stored snapshot and restored the page data
Both Events have a method to get access to the current Snapshot Store Object (EVENT::getSnapshotStoreObject())
For examples have a look into the integrated EventListener for handling ttc_content and sys_file_references

__________________________________________________________________________________________________________________

## Caveats
This extension is currently in an explicit alpha state.
- Language handling for page overlays will be integrated in the next version
- Sys_file_refernces handling for pages and page_overlays will also be integrated in the next version
- Extension is tested only with connected localisations and without **workspaces(!)**
