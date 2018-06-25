<?php

namespace app\controllers\admin;

use Yii;
use yii\debug\Module;
use yii\helpers\Url;
use yii\web\Controller;

class PublicController extends Controller
{

    public $layout = false;

    public $debugTags;

    public $beforeAction = true;
    public $checkLogin = true;

    public function beforeAction($action)
    {

        if($this->beforeAction && !$this->isInstalled()){
            return $this->redirect(['home/install/step1'])->send();
        }

        if($this->checkLogin && Yii::$app->user->isGuest){
            return $this->redirect(['home/account/login'])->send();
        }

        if(!Yii::$app->user->identity->isAdmin){
            return $this->error('抱歉，您无权访问');
        }

        return true;

    }

    /** 展示模板
     * @param $view
     * @param array $params
     * @return string
     */
    public function display($view, $params = [])
    {

        if(YII_DEBUG === true){
            $tags = array_keys($this->getDebugTags());
            $tag  = reset($tags);

            $params['toolbarTag'] = $tag;
        }

        exit($this->render($view . '.html', $params));

    }

    /**
     * 成功消息提示
     * @param $message 提示信息
     * @param int $jumpSeconds 延迟时间
     * @param string $jumpUrl 跳转链接
     * @param null $model
     * @return string
     */
    public function success($message, $jumpSeconds = 1, $jumpUrl = '', $model = null)
    {

        $jumpUrl = $jumpUrl ? Url::toRoute($jumpUrl) : \Yii::$app->request->referrer;

        return $this->display('/home/public/message', ['flag' => 'success', 'message' => $message, 'time' => $jumpSeconds, 'url' => $jumpUrl, 'model' => $model]);

    }

    /**
     * 错误消息提示
     * @param $message
     * @param int $jumpSeconds
     * @param string $jumpUrl
     * @return string
     */
    public function error($message, $jumpSeconds = 3, $jumpUrl = '')
    {

        $jumpUrl = $jumpUrl ? Url::toRoute($jumpUrl) : \Yii::$app->request->referrer;

        return $this->display('/home/public/message', ['flag' => 'error', 'message' => $message, 'time' => $jumpSeconds, 'url' => $jumpUrl]);

    }

    /**
     * 判断是否已经安装过
     * @return bool
     */
    public function isInstalled()
    {
        return file_exists(Yii::getAlias("@runtime") . '/install/install.lock');
    }

    private function getDebugTags($forceReload = false)
    {
        if ($this->debugTags === null || $forceReload) {
            if ($forceReload) {
                clearstatcache();
            }

            $indexFile = Module::getInstance()->dataPath . '/index.data';

            $content = '';
            $fp = @fopen($indexFile, 'r');
            if ($fp !== false) {
                @flock($fp, LOCK_SH);
                $content = fread($fp, filesize($indexFile));
                @flock($fp, LOCK_UN);
                fclose($fp);
            }

            if ($content !== '') {
                $this->debugTags = array_reverse(unserialize($content), true);
            } else {
                $this->debugTags = [];
            }
        }

        return $this->debugTags;

    }

}