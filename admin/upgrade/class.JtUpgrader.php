<?php
/**
 * Manage Upgrade of JournalTouch (well, the class might be used for anything else ;))
 *
 * Basically upgrades should work similar to e.g. Wordpress. A user just downloads
 * the newest version, extracts it and overwrites old files. Subsequently an
 * upgrader does the remaining house keeping tasks.
 *
 * How it works
 * 1.   If an upgrade was completed successfully, the version number is written in
 *      data/upgraded (as filename).
 * 2.   For each version an upgrade file with specific actions reflecting the
 *      necessary changes since the last version can exist (must not).
 *      Location: admin\upgrade\version ; Format "upgrade_%versionnumber%.php"
 *
 * This way upgrades can be done from any version to the newest one. (Even though
 * it seems unlikely that JT will have a lot of these - but this is the reason
 * I'm not looking for a full-blown existing solution).
 *
 * @note:   This probably is flexible enough to handle more possible upgrade
 *          changes. Currently there is no method for deleting files, but it
 *          would be easy to add without breaking anything. Same goes for e.g.
 *          database upgrade.
 *
 *
 * Time-stamp: "2015-09-29 00:00:00 Zeumer"
 *
 * @author Tobias Zeumer <tobias.zeumer@tuhh.de>
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
class JtUpgrader {
    /// \brief \b STR @see sys/bootstrap.php
    protected $basepath;
    /// \brief \b STR Convenience - upgrade directory path
    protected $upgradedir;

    /// \brief \b ARY List of upgrades that are not yet applied
    protected $missing_upgrades;

    // @note:   Create: files and folder are created by extracting a new version
    //          above the old installation. So this should be all we need.
    /// \brief \b STR Version: Release Note or e.g. information what to do by hand
    protected $release_note;
    /// \brief \b ARY Version: Folders to delete (might be single files too, but not pattern supported)
    protected $release_foldersDelete;
    /// \brief \b ARY Version: Files to move (x['from'][] + x['to'][]; wildcards allowed
    protected $release_filesMove;


    /// \brief \b STR
    public $status_log = '';
    /// \brief \b STR
    public $status_message = '';


    /**
     * @brief   This upgrader nearly can autorun
     *
     * @return \b void
     */
    public function __construct($cfg) {
        $this->basepath     = $cfg->sys->basepath;
        $this->upgradedir   = $this->basepath.'admin/upgrade/';
        $this->history      = $cfg->sys->data_upgraded;
    }


    /**
     * @brief   This is our controller for the whole upgrade procedure
     *
     * @return \b BOL true on success, false on fail
     */
    public function start_upgrade() {
        $status = true;

        // Get versions with outstanding local upgrades
        $this->missing_upgrades = $this->_check_unapplied_upgrades();

        // Iterate through the upgrades (if any)
        if ($this->missing_upgrades) {
            $status = $this->_apply_upgrades();
        } else {
            $this->status_message .= 'Everything already up to date.';
        }

        return $status;
    }


    /**
     * @brief   Check which upgrades are already done, check which still need to
     *          be done
     *
     * @return \b ARY with all necessary upgrades, or false if empty
     */
    private function _check_unapplied_upgrades() {
        // Find all possible upgrades
        $upgrade_files = glob($this->upgradedir.'versions/upgrade_*');
        // Remove everything but version number (only needed for pattern search and because of .gitkeep)
        $upgrade_files = preg_replace('/.*versions\/upgrade_(.*)\.php/', '\1', $upgrade_files);

        // Find all already completed upgrades
        $completed = glob($this->history.'ver_*');
        // Remove everything but version number (only needed for pattern search and because of .gitkeep)
        $completed = preg_replace('/.*upgraded\/ver_/', '', $completed);

        // Finally only keep the upgrade_files in memory that have not been run yet
        $upgrade_todo = array_diff($upgrade_files, $completed);

        if (count($upgrade_todo) == 0) $upgrade_todo = false;

        return $upgrade_todo;
    }


    /**
     * @brief   Check which upgrades are already done, check which still need to
     *          be done
     *
     * @return \b ARY with all necessary upgrades, or false if empty
     */
    private function _apply_upgrades() {
        foreach ($this->missing_upgrades AS $version) {
            $status = true;
            require($this->upgradedir.'versions/upgrade_'.$version.'.php');

            $this->status_message .= '<h2>Updating to version '.$version.'</h2>';

            // Check all folder exist and are writable, else exit loop
            if (!$status = $this->_check_writable()) break;

            // Go on with moving the files, on fail break
            if (!$status = $this->_files_move()) break;

            // Go on with deleting folder, on fail break
            if (!$status = $this->_folders_delete()) break;

            // Everything went great? Ok, finish the upgrade
            if ($status) {
                file_put_contents($this->history.'ver_'.$version, $this->release_note);
            }

            $this->status_message .= 'Successfully upgraded to: <strong>'.$version.'</strong><br>';
        }

        return $status;
    }


    /**
     * @brief   Make sure all folders for an upgrade are writable before doing
     *          anything. All new folder are are part of the release, so we don't
     *          have to check if those exist.
     *
     * @return \b BOOL true if ok, false on fail. On fail add $this->status_message
     */
    private function _check_writable() {
        $status_log = '';

        // Get all up to the last slash (before the file name or pattern) - from
        $folders_src = $this->release_filesMove['from'];
        $folders_src = preg_replace('/(.*)\/.*/', '\1', $folders_src);

        // Get all up to the last slash (before the file name or pattern) - to
        $folders_dest = $this->release_filesMove['to'];
        $folders_dest = preg_replace('/(.*)\/.*/', '\1', $folders_dest);

        // create unique array with all folders to delete
        $folders_del = $this->release_foldersDelete;

        // Check if all src folders exist. If not, they neither need to be removed
        // nor something has to be moved from them
        // @todo: currently would print redudant message if specific single files
        //        from a single folder were to move; but we need the key mapping
        //        to the original array
        $status_log .= '<h3>Checking old folders for moving files to new folders</h3><p>';
        foreach ($folders_src AS $key => $folder) {
            $do_unset = false;

            if (!file_exists($this->basepath.$folder)) {
                $status_log .= "This source folder didn't exist (this is no error)<strong>: $folder</strong><br>";
                $do_unset = true;
            }

            // Also check if any file matches the move pattern, otherwise nothing
            // to do
            $matches = glob($this->basepath.$this->release_filesMove['from'][$key]);
            if (!$do_unset && count($matches) == 0) {
                $do_unset = true;
            }

            if ($do_unset == true) {
                // unset this src; also in property
                unset($folders_src[$key]);
                unset($this->release_filesMove['from'][$key]);
                // if there is no src, there isn't a target
                unset($folders_dest[$key]);
                unset($this->release_filesMove['to'][$key]);
            }
        }
        $status_log .= '</p>';

        // Do it again: Check if all del folders exist.
        $status_log .= '<h3>Checking writable: old folders that finally should be deleted and folders where files must be moved to</h3><p>';
        foreach ($folders_del AS $key => $folder) {
            if (!file_exists($this->basepath.$folder)) {
                $status_log .= "This folder was already deleted (this is no error)<strong>: $folder</strong><br>";
                // unset this del
                unset($folders_del[$key]);
                unset($this->release_foldersDelete[$key]);
            }
        }
        $status_log .= '</p>';


        $folderList = array_merge ($folders_del, $folders_src, $folders_dest);

        // Sort and get only unique values
        $folderList = array_unique($folderList);
        sort($folderList);

        // Check each folder, if it is writeable
        $status  = true;
        foreach ($folderList AS $folder) {
            if (!is_writable($this->basepath.$folder)) {
                $status_log .= 'Please make sure this folder exists and is writable: <strong>'.$folder.'</strong><br/>';
                $status_log .= 'You also can delete files and folder yourself by hand. See list below.<br />';
                $status  = false;
            } else {
                $status_log .= 'Folder is writable: <strong>'.$folder.'</strong><br/>';
            }
        }
        $status_log .= '</p>';

        //Just for testing: $status = false;
        // Offer user to do it himself
        if (!$status) {
            $status_log .= '<h3>Errors on updating</h3><p>There were errors. You can either correct the folder permissions or do the following steps by hand. Aftwerwards rerun this script</p>';

            $status_log .= '<h4>Move these files</h4><table><tr><th>Move from</th><th>Target</th></tr>';
            foreach ($this->release_filesMove['from'] AS $key => $src) {
                $dest = $this->release_filesMove['to'][$key];
                $status_log .= '<tr><td>'.$src.'</td><td>'.$dest.'</td></tr>';
            }
            $status_log .= '</table>';

            $status_log .= '<h4>Afterwards delete these folders and files</h4><ul>';
            foreach ($this->release_foldersDelete AS $target) {
                $status_log .= '<li>'.$target.'</li>';
            }
            $status_log .= '</ul>';
        }

        if ($status) {
            $this->status_message .= 'Folder check: <strong>success</strong><br>';
        } else {
            $this->status_message .= 'Folder check: <strong>FAILED</strong><br>';
        }

        $this->status_log .= $status_log;

        return $status;
    }


    /**
     * @brief   Move the files
     *
     * @return \b BOOL true if ok, false on fail.
     */
    private function _files_move() {
        $status = true;

        // Check if anything is to do at all, otherwise return
        if (!count($this->release_filesMove['from'])) return $status;

        $status_log = '<h3>Moving files to new directories</h3>';
        foreach ($this->release_filesMove['from'] AS $key => $src)
            $dest_path = $this->release_filesMove['to'][$key];

            $src_matches = glob($this->basepath.$src);
            foreach ($src_matches AS $src_filepath) {
                $src_filename = basename($src_filepath);

                // If it is a single file in 'from' then there is no * in 'to'
                // Otherwise, replace the star with the single file we want to
                // move
                $dest_filepath  = $this->basepath;
                $dest_filepath .= str_replace('*', $src_filename, $dest_path);
                //echo "SRC: $src_filepath <br>DEST: $dest_filepath<br>";

                $status = copy($src_filepath, $dest_filepath);

                // Successfully copied?
                if ($status) {
                    $status_log .=  "<p>Successfully copied $src_filepath to $dest_filepath<br>";
                } else {
                    $status_log .=  "FAILED copying $src_filepath to $dest_filepath<br><strong>UPGRADE STOPPED</strong><br>";
                    break;
                }

                // Ok copied, now delete src
                $status = unlink($src_filepath);

                // Successfully deleted?
                if ($status) {
                    $status_log .=  "Successfully deleted $src_filepath</p>";
                }
                // Otherwise break loop
                else {
                    $status_log .=  "FAILED deleting $src_filepath<br><strong>UPGRADE STOPPED</strong><br>";
                    break;
                }

                // Break this loop too on error
                if (!$status) break;
           }

        if ($status) {
            $this->status_message .= 'Coyping and deleting files: <strong>success</strong><br>';
        } else {
            $this->status_message .= 'Coyping and deleting files: <strong>FAILED</strong><br>';
        }

        $this->status_log .= $status_log;

        return $status;
    }



    /**
     * @brief   Delete the folders
     *
     * @return \b BOOL true if ok, false on fail.
     */
    private function _folders_delete() {
        $status = true;

        // Check if anything is to do at all, otherwise return
        if (!count($this->release_foldersDelete)) return $status;

        $status_log = '<h3>Deleting old folders</h3>';
        foreach ($this->release_foldersDelete AS $folder) {
            $folder_path = $this->basepath.$folder;

            // Delete possible leftover files (empty directory for deletion)
            $rdi = new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    $status = rmdir($file->getRealPath());
                } else {
                    $status = unlink($file->getRealPath());
                }
            }
            // Folder empty? Now remove it
            $status = rmdir($folder_path);

            // Successfully deleted?
            if ($status) {
                $status_log .=  "Successfully deleted $folder_path<br>";
            }
            // Otherwise break loop
            else {
                $status_log .=  "FAILED deleting $folder_path<p><strong>UPGRADE STOPPED</strong></p>";
                break;
            }

            // Break this loop on error
            if (!$status) break;
       }

        if ($status) {
            $this->status_message .= 'Deleting folders: <strong>success</strong><br>';
        } else {
            $this->status_message .= 'Deleting folders: <strong>FAILED</strong><br>';
        }

        $this->status_log .= $status_log;

        return $status;
    }




    private function x($a) {
        echo '<pre>';
        print_r($a);
        die();
    }
}