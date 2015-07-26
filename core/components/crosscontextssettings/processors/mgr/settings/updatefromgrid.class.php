<?php

/**
 * CrossContextsSettings
 *
 * Copyright 2014-2015 by goldsky <goldsky@virtudraft.com>
 *
 * This file is part of CrossContextsSettings, a custom plugin to manage cross
 * contexts' settings
 *
 * CrossContextsSettings is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation version 3,
 *
 * CrossContextsSettings is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * CrossContextsSettings; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * CrossContextsSettings processor script
 *
 * @package crosscontextssettings
 * @subpackage processor
 */
class SettingUpdateFromGridProcessor extends modObjectProcessor {

    public $classKey = 'modContextSetting';
    public $languageTopics = array('crosscontextssettings:default');
    public $objectType = 'crosscontextssettings.settingsupdatefromgrid';

    public function initialize() {
        $data = $this->getProperty('data');
        if (empty($data))
            return $this->modx->lexicon('invalid_data');
        $data = $this->modx->fromJSON($data);
        if (empty($data))
            return $this->modx->lexicon('invalid_data');
        $this->setProperties($data);
        $this->unsetProperty('data');

        return parent::initialize();
    }

    public function process() {
        $props = $this->getProperties();
        foreach ($props as $k => $v) {
            if ($k === 'key' ||
                    $k === 'action' ||
                    $k === 'menu'
            ) {
                continue;
            }
            $setting = $this->modx->getObject($this->classKey, array(
                'key' => $props['key'],
                'context_key' => $k,
            ));
            if (!empty($v) || $v === '0') {
                if (!$setting) {
                    if (isset($props[$k])) {
                        $setting = $this->modx->newObject($this->classKey);
                        $setting->set('key', $props['key']);
                        $setting->set('context_key', $k);
                        $setting->set('value', $props[$k]);
                        if ($setting->save() === false) {
                            $message = $this->modx->lexicon('crosscontextssettings.err_setting_save', array('key' => $props['key'], 'context' => $k));
                            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
                            $this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': [CCS] ' . $message);
                            continue;
                            return $this->failure($message);
                        } else {
                            $this->modx->cacheManager->refresh(array('context_settings' => array('contexts' => '$k')));
                        }
                    }
                    continue;
                }
                if($setting->get('value') === $props[$k]) { //Skip saving same value
                    continue;
                }
                $setting->set('value', $props[$k]);
                if ($setting->save() === false) {
                    $message = $this->modx->lexicon('crosscontextssettings.err_setting_save', array('key' => $props['key'], 'context' => $k));
                    $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
                    $this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': [CCS] ' . $message);
                    return $this->failure($message);
                } else {
                    $this->modx->cacheManager->refresh(array('context_settings' => array('contexts' => '$k')));
                }
            } else {
                if ($setting) {
                    $setting->remove();
                    $this->modx->cacheManager->refresh(array('context_settings' => array('contexts' => '$k')));
                }
            }
        }
        return $this->success();
    }

}

return 'SettingUpdateFromGridProcessor';
