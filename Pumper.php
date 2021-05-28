<?
 

require_once 'vendor/autoload.php';
   use GuzzleHttp\Client;
    use GuzzleHttp\Exception\GuzzleException;
class Pumper
{
    
    /**
     * Ключ сервиса rucaptcha
     * */
    const CAPTCHA_API_TOKEN = 'token';

    /**
     * Публичный ключ сайта от recaptcha
     * */
    const RECAPTCHA_PUBLIC_SITE_KEY = 'sitekey';

    /**
     * Действие которое проверяется recaptcha
     * */
    const RECAPTCHA_SITE_ACTION = 'verify';

    /*
     * Коды ошибок сервиса rucaptcha
     * */
    const RUCAPTCHA_ERROR_WRONG_USER_KEY = 'ERROR_WRONG_USER_KEY';
    const RUCAPTCHA_ERROR_KEY_DOES_NOT_EXIST = 'ERROR_KEY_DOES_NOT_EXIST';
    const RUCAPTCHA_ERROR_ZERO_BALANCE = 'ERROR_ZERO_BALANCE';
    const RUCAPTCHA_ERROR_PAGEURL = 'ERROR_PAGEURL';
    const RUCAPTCHA_ERROR_NO_SLOT_AVAILABLE = 'ERROR_NO_SLOT_AVAILABLE';
    const RUCAPTCHA_ERROR_MAX_USER_TURN = 'MAX_USER_TURN';
    const RUCAPTCHA_ERROR_BAD_PROXY = 'ERROR_BAD_PROXY';

    /*
     * Максимальное кол-во попыток для запросов к rucaptcha
     * */
    const COUNT_MAX_REQUEST = 4;

    public $dbs;
    public $path_json;
    public $path_task;
    public $path_pull;
    public $path_acclist;
    public $path_cookie;
    public $accounts;
    public $pull;
    public $timezone;

    public $checknow;

    function __construct($param = array())
    {

print_r("sdfsdf");
        if (isset($param['config'])) {
            $this->dbs = new SafeMySQL($param['config']);
        } else {
            $this->dbs = null;
        }

        $this->timezone = 'Asia/Krasnoyarsk';
        $this->accounts = [];
        $this->pull = [];

        $this->checknow = null;

        $this->path_json = $_SERVER['DOCUMENT_ROOT'] . '*****';
        $this->path_task = $_SERVER['DOCUMENT_ROOT'] . '*****';
        $this->path_pull = $_SERVER['DOCUMENT_ROOT'] . '*****';
        $this->path_acclist = $_SERVER['DOCUMENT_ROOT'] . '*****';
        $this->path_cookie = $_SERVER['DOCUMENT_ROOT'] . '*****';

    }


    // УДалить дубликат задачи
    function deleteDuplicate()
    {

        $taskFromPullNow = json_decode(file_get_contents($this->path_json . '*****'), true);
        $keys = array_keys($taskFromPullNow);
        $result = scandir( $this->path_task);

        foreach ($result as $item) {
            $item = preg_replace("/[^,0-9]/", '', $item);

            if( in_array($item, $keys )) {
                unlink($this->path_task . $item . '.json');
            }

        }
        return true;
    }

// Список аккаунтов
    function listAccounts()
    {

        $list = scandir($this->path_cookie);
        $list = array_slice($list, 2, count($list));

        if (count($list) > 0) {
            $this->accounts = $list;
        }

        ob_start();
        print_r($list);
        $debug = ob_get_contents();
        ob_end_clean();
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'w+');
        fwrite($fp, $debug);
        fclose($fp);
    }

// обновить список задач
    function updatePull($aPull = [])
    {
        
        if (count($aPull) == 0) {
            $aPull = [];
        }
        $fp = fopen($this->path_json . '*****', 'w+');
        fwrite($fp, json_encode($aPull));
        fclose($fp);
    }

// получить список задач (задачи для отчета)
    function getPull()
    {
        if (file_exists($this->path_json . '*****')) {
            ob_start();
            include $this->path_json . '*****';
            $json = ob_get_contents();
            ob_end_clean();
            $json = json_decode($json, true);
        } else {
            $json = [];
        }

        return $json;
    }

// Удаляет задачу из списка
    function removePull($code)
    {

        if (count($this->pull) > 0) {
            $aTemp = [];
            foreach ($this->pull as $v) {
                if ($v['code'] != $code) {
                    $aTemp[] = $v;
                }
            }

            $this->pull = $aTemp;

            ob_start();
            print_r($aTemp);
            print_r($this->pull);
            $debug = ob_get_contents();
            ob_end_clean();
            $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'w+');
            fwrite($fp, $debug);
            fclose($fp);

        }

    }

// Добавить задачу
    function add_task($param = array())
    {
        if (!empty($param['task_id'])) {
            $fp = fopen($this->path_task . $param['task_id'] . $param['comment_id'] . '.*****', 'w+');
            fwrite($fp, json_encode($param));
            fclose($fp);
        }
    }

// Возвращает список задач
    function list_task($param = array())
    {
        $list = scandir($this->path_task);
        $list = array_slice($list, 2, count($list));

        if (count($list) > 0) {

            //$ind=0;
            //$aTaskList = [];
            //$this->pull = [];
            $aTaskList = $this->getPull();

            //$date = new \DateTime('now', new \DateTimeZone($this->timezone));

            foreach ($list as $file) {
                if (file_exists($this->path_task . $file)) {
                    ob_start();
                    include $this->path_task . $file;
                    $json = ob_get_contents();
                    ob_end_clean();
                    $json = json_decode($json, true);

                    for ($i = 1; $i <= $json['qtty']; $i++) {
                        //if(isset($this->accounts[$ind])){
                        //$this->pull[] = ['post_id'=>$json['post_id'],'comment_id'=>$json['comment_id'],'account'=>$this->accounts[$ind]];
                        //$date->modify('+3 second');

                        $json['code'] = $json['task_id'] . $json['comment_id'];
                        //$json['account'] = $this->accounts[$ind];
                        //$json['time'] = $date->getTimestamp();

                        $this->pull[] = $json;
                        //$ind++;
                        //}
                    }

                    unlink($this->path_task . $file);

                    $aTaskList[$json['task_id'] . $json['comment_id']] = ['code' => $json['task_id'] . $json['comment_id'], 'status' => false, 'notice' => 200, 'qtty' => 0];

                    ob_start();
                    echo "#[" . $this->now() . "] Постановка задачи: " . $json['qtty'] . "\r\n";
                    $debug = ob_get_contents();
                    ob_end_clean();
                    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****' . $json['task_id'] . '-' . $json['comment_id'] . '.log', 'a+');
                    fwrite($fp, $debug);
                    fclose($fp);

                }
            }

            $this->updatePull($aTaskList);
            $date = new \DateTime('now', new \DateTimeZone($this->timezone));
            $now = $date->format('H-i-s');

            $this->updateRoute('vote');
            //$this->updateRoute('stop');
        } else {

            if (isset($this->pull[0])) {
                $this->updateRoute('vote');
            } elseif (!isset($this->pull[0])) {
                $this->updateRoute('check');
            } else {
                $this->updateRoute('start');
            }

        }
    }

// Сохраняет в базу данных аккаунты, которые хранятся локально в накрутчике
    function checkTask()
    {
    
        //Получаем список заданий
        $aTaskList = $this->getPull();

        // Делаем выборку задач в работе
        $temp = [];
        if (count($this->pull) > 0) {

            foreach ($this->pull as $v) {
                $temp[$v['code']] = true;
            }
        }

        if (count($aTaskList) > 0) {
            $this->updateRoute('vote');
            foreach ($aTaskList as $k => $v) {
                if (!$v['status']) {
                    if (!isset($temp[$v['code']])) {

                        $date = new \DateTime('now', new \DateTimeZone($this->timezone));
                        $now = $date->format('H-i-s');
                        $aTaskList[$k]['status'] = true;

                        ob_start();
                        print_r($aTaskList);
                        //print_r($this->pull);
                        $debug = ob_get_contents();
                        ob_end_clean();
                        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****' . $v['code'] . '-' . $now . '.log', 'w+');
                        fwrite($fp, $debug);
                        fclose($fp);
                        $this->updateRoute('report');
                    }
                }
                $this->updatePull($aTaskList);
            }

            $this->generateTime();
        } else {
            $this->updateRoute('start');
        }

    }

// Сохраняет в базу данных аккаунты, которые хранятся локально в накрутчике
    function generate()
    {

        $json = [
            '58963435' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '58963374' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '58963430' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '58963436' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '58963437' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '58963438' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '59020925' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
            '59020932' => ['name' => '*****', 'plus' => 3, 'minus' => 2],
        ];


        ob_start();
        echo json_encode($json);
        $debug = ob_get_contents();
        ob_end_clean();
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'w+');
        fwrite($fp, $debug);
        fclose($fp);

    }

// Возвращает текущее состояние роутера

    function getRoute()
    {
        if (file_exists($this->path_json . '*****')) {
            ob_start();
            include $this->path_json . '*****';
            $json = ob_get_contents();
            ob_end_clean();

            $json = json_decode($json, true);
        } else {
            $json = ['route' => 'none'];
        }

        return $json['route'];
    }

// Обновить текущее состояние роутера

    function updateRoute($command)
    {

        if (file_exists($this->path_json . '*****')) {
            ob_start();
            include $this->path_json . '*****';
            $json = ob_get_contents();
            ob_end_clean();

            $json = json_decode($json, true);
        } else {
            $json = ['route' => 'start'];
        }

        $json['route'] = $command;

        $fp = fopen($this->path_json . '*****', 'w+');
        fwrite($fp, json_encode($json));
        fclose($fp);

        ob_start();
        echo $command . "\r\n";
        $debug = ob_get_contents();
        ob_end_clean();
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'a+');
        fwrite($fp, $debug);
        fclose($fp);

    }
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Задает следующее время для голосования
    function generateTime($cool = 'N')
    {

        $numb = rand(10, 30);

        $date = new \DateTime('now', new \DateTimeZone($this->timezone));
        if ($cool == 'N') {
            $date->modify('+' . (int)$numb . ' second');
        } else {
            $date->modify('+20 second');
        }
        $ts = $date->getTimestamp();
        $now = $date->format('H:i:s d-m-Y');
        $this->checknow = $now;
        $fp = fopen($this->path_json . 'time.json', 'w+');
        fwrite($fp, json_encode(['timestamp' => $ts]));
        fclose($fp);


    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function checkTime()
    {

        if (file_exists($this->path_json . '*****')) {
            ob_start();
            include $this->path_json . '*****';
            $json = ob_get_contents();
            ob_end_clean();
            $json = json_decode($json, true);

            if (isset($json['timestamp'])) {


                $date = new \DateTime('now', new \DateTimeZone($this->timezone));
                $now = $date->format('H:i:s d-m-Y');

                $date2 = new \DateTime();
                $date2->setTimezone(new DateTimeZone($this->timezone));
                $date2->setTimestamp($json['timestamp']);
                $times = $date2->format('H:i:s d-m-Y');

                $interval = $date->diff($date2);

                return ($interval->invert == 1) ? true : false;

            }

        }
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function report()
    {
        $aTaskList = $this->getPull();

        if (count($aTaskList) > 0) {
            foreach ($aTaskList as $k => $task) {
                if ($task['status']) {

                    ob_start();
                    print_r($task);
                    $debug = ob_get_contents();
                    ob_end_clean();
                    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****' . $task['code'] . '.log', 'w+');
                    fwrite($fp, $debug);
                    fclose($fp);

                    $this->sendReport($task);
                    unset($aTaskList[$k]);
                }
            }

            $this->updatePull($aTaskList);
        }


    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function cancelTask($code, $notice)
    {
        $aTaskList = $this->getPull();
        if (isset($aTaskList[$code])) {
            $aTaskList[$code]['notice'] = $notice;
            $this->updatePull($aTaskList);
        }
    }
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Отправляем отчет о завершении голосования по задачи
    function sendReport()
    {
        $args = func_get_args();
        $now = strtotime("now");
                ob_start();
                print_r($args);
                $debug = ob_get_contents();
                ob_end_clean();
                $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'w+');
                fwrite($fp, $debug);
                fclose($fp);
        if (count($args)) {

            $aParams = $args[0];
            $link = '*****';


            $uagent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36 OPR/58.0.3135.107";

            if (!empty($aParams['code'])) {
                /*
                $aFields = [
                'code'=>$aParams['code'],
                'qtty'=>$aParams['qtty']
                ];
                */
                ob_start();
                print_r($aParams);
                $debug = ob_get_contents();
                ob_end_clean();
                $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'w+');
                fwrite($fp, $debug);
                fclose($fp);

                $options = array(
                    CURLOPT_URL => $link,
                    CURLOPT_USERAGENT => $uagent,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $aParams,
                );

                $ch = curl_init();
                curl_setopt_array($ch, $options);

                $content = curl_exec($ch);
                $header = curl_getinfo($ch);

                $err = curl_errno($ch);
                $errmsg = curl_error($ch);

                curl_close($ch);

                $response['content'] = json_decode($content, true);
                $response['errno'] = $err;
                $response['errmsg'] = $errmsg;


            }


            ob_start();
            print_r($response);
            $debug = ob_get_contents();
            ob_end_clean();
            $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'w+');
            fwrite($fp, $debug);
            fclose($fp);

        }
        $this->updateRoute('vote');
    }

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function getAccount($code)
    {

        if (file_exists($this->path_acclist . $code . '.json')) {
            ob_start();
            include $this->path_acclist . $code . '.json';
            $json = ob_get_contents();
            ob_end_clean();
            $aList = json_decode($json, true);
        } else {
            $list = scandir($this->path_cookie);
            $aList = array_slice($list, 2, count($list));
        }


        if (isset($aList[0])) {
            $file = array_shift($aList);
            $cookie = $this->parseCookie($file);

            $fp = fopen($this->path_acclist . $code . '.json', 'w+');
            fwrite($fp, json_encode($aList));
            fclose($fp);

            return ['status' => true, 'cookie' => $cookie];
        } else {
            return ['status' => false, 'code' => 310];
        }
    }

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function parseCookie($code)
    {

        // read the file
        //if(file_exists($this->path_cookie.$code.".txt")){
        if (file_exists($this->path_cookie . $code)) {
            $lines = file($this->path_cookie . $code);

            // var to hold output
            $trows = '';

            // iterate over lines
            foreach ($lines as $line) {

                // we only care for valid cookie def lines
                if ($line[0] != '#' && substr_count($line, "\t") == 6) {

                    // get tokens in an array
                    $tokens = explode("\t", $line);

                    // trim the tokens
                    $tokens = array_map('*****', $tokens);

                    if (isset($tokens[5]) && isset($tokens[6])) {

                        if ($tokens[5] == "*****") {

                            $trows .= $tokens[5] . "=" . $tokens[6] . ";";
                        }
                        if ($tokens[5] == "*****") {

                            $trows .= $tokens[5] . "=" . $tokens[6] . ";";
                        }


                    }

                }

            }
            $trows .= '*****';

            return $trows;
        }

        return false;

    }

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    /**
     * Ставит задачу в сервис rucaptcha для разгадывания кода
     * @param $pageUrl
     * @return string
     * @throws GuzzleException
     */
    function getRucaptchaTaskId($pageUrl): string
    {
        $client = new Client();

        $isTaskSetComplete = false;
        $taskId = '';

        /*
         * Счетчик попыток получить ответ от rucaptcha
         * */
        $countIterations = 0;

        while (!$isTaskSetComplete) {
            if ($countIterations > self::COUNT_MAX_REQUEST)
                break;

            $getTaskUrl = 'https://rucaptcha.com/in.php?key=' . self::CAPTCHA_API_TOKEN . '&method=userrecaptcha&json=1&version=v3&min_score=0.7&googlekey=' . self::RECAPTCHA_PUBLIC_SITE_KEY . '&pageurl=' . $pageUrl;

            $response = $client->post($getTaskUrl);
            $task = json_decode($response->getBody(), true);
            $task['get_taskUrl'] = $getTaskUrl;
            if ($task['status'] == 1) {
                $isTaskSetComplete = true;
                $taskId = $task['request'];
            } else {
                logger($task, 'rucaptcha/rucaptcha_error.log', 'a+');
                sleep(7);
            }

            $countIterations++;
        }

        return $taskId;
    }

    /**
     * Получает g-recaptcha-response на основе id задачи rucaptcha
     * @param $taskId
     * @return string
     * @throws GuzzleException
     */
    function getRecaptchaResponse($taskId): string
    {
        $client = new Client();

        $isResponseGetComplete = false;
        $gRecaptchaResponse = '';

        /*
         * Счетчик попыток получить ответ от rucaptcha
         * */
        $countIterations = 0;

        while (!$isResponseGetComplete) {
            if ($countIterations > self::COUNT_MAX_REQUEST)
                break;

            $getTokenUrl = 'https://rucaptcha.com/res.php?key=' . self::CAPTCHA_API_TOKEN . '&json=1&action=get&id=' . $taskId;
            $response = $client->post($getTokenUrl);;
            $task = json_decode($response->getBody(), true);

            if ($task['status'] == 1) {
                $isResponseGetComplete = true;
                $gRecaptchaResponse = $task['request'];
            } else {
                logger($task, 'rucaptcha/rucaptcha_error.log', 'a+');
                sleep(7);
            }

            $countIterations++;
        }

        return $gRecaptchaResponse;
    }


  function vo()
    {

        $args = func_get_args();
        $option = $args[0];
        $opt = $this->getAccount($option['task_id'] . $option['comment_id']);

        if ($opt['status']) {

            //$cookie = $this->parseCookie($code);
            $cookie = $opt['cookie'];
            $c = explode(';', $cookie);
            $c = explode(':', $c[0]);
            $userId = $c[1];

            try {
                $taskId = $this->getRucaptchaTaskId('*****' . $option['*****'] . '/');
            } catch (GuzzleException $e) {
               // $this->logger($e->getMessage());
            }

          //  if (!$taskId) $this->logger('Необработанная ошибка при получении id задачи rucaptcha');

            sleep(15);

            try {
                $gRecaptchaResponse = $this->getRecaptchaResponse($taskId);
            } catch (GuzzleException $e) {
               // $this->logger($e->getMessage());
            }

         //   if (!$gRecaptchaResponse) $this->logger('Необработанная ошибка при получении g-recaptcha-response');


            $request = array(
                'commentId' => $option['comment_id'],
                'vote' => 1,
                'user' => $userId,
                'regionId' => 66,
                'g-recaptcha-response' => $gRecaptchaResponse,
            );


            if (true) {
                
                $url = '*****' . $option['*****'] . '*****';
                $uagent = "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36";
                $referer = '*****' . $option['*****'] . '/';
                $aTaskList = $this->getPull();

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
                curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
                curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0); // следовать за редиректами
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));

                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'User-Agent: ' . $uagent,
                    'Accept: **********',
                    'Referer: ' . $referer,
                    'Cookie: ' . $cookie
                ));


                curl_setopt($ch, CURLOPT_REFERER, $referer);

                $content = curl_exec($ch);
                $err = curl_errno($ch);
                $errmsg = curl_error($ch);
                $header = curl_getinfo($ch, CURLINFO_HEADER_OUT);
                $header = curl_getinfo($ch);
                curl_close($ch);

                $header['errno'] = $err;
                $header['errmsg'] = $errmsg;
                $header['content'] = $content;
                //$header['prox'] = $proxy["ip"].':'.$proxy["port"];
                $header['proxy'] = $proxy;
                $header['cookie'] = $cookie;

                $json = json_decode($content, true);

                if (!empty($content) && $header['http_code'] != 403) {
                    $json = json_decode($content, true);

                    try {
                        $this->sendGoodReport($taskId);
                    } catch (GuzzleException $e) {
                     //   $this->logger($e->getMessage());
                    }

                } else {

                    try {
                        $this->sendBadReport($taskId);
                    } catch (GuzzleException $e) {
                     //   $this->logger($e->getMessage());
                    }

                    ob_start();
                    echo $userId . "| Неудача \n";
                    $debug = ob_get_contents();
                    ob_end_clean();
                    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****', 'a+');
                    fwrite($fp, $debug);
                    fclose($fp);

                    return $this->vo($option);
                }

                ob_start();
                print_r($request);
                print_r($option);
                print_r($json);
                //print_r($header);
                $debug = ob_get_contents();
                ob_end_clean();
                $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****' . $option['*****'] . '-' . $option['*****'] . '.*****', 'a+');
                fwrite($fp, $debug);
                fclose($fp);
            }

            

            //if(true){
            if (!empty($json['votesPlus'])) {

                if (isset($aTaskList[$option['task_id'] . $option['comment_id']])) {

                    ob_start();
                    echo "+[" . $this->now() . "]: plus: " . $json['*****'] . "; minus:" . $json['*****'] . "\r\n";
                    $debug = ob_get_contents();
                    ob_end_clean();
                    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/logs/vote/' . $option['task_id'] . '-' . $option['comment_id'] . '.log', 'a+');
                    fwrite($fp, $debug);
                    fclose($fp);

                    $aTaskList[$option['task_id'] . $option['comment_id']]['qtty']++;
                    $this->updatePull($aTaskList);
                }


                //$json = json_decode($content,true);

                return ['status' => true, 'code' => 200, 'msg' => 'отлично'];
            } else {

                ob_start();
                echo "-[" . $this->now() . "]: " . $json['status'] . "\r\n";
                $debug = ob_get_contents();
                ob_end_clean();
                $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '*****' . $option['task_id'] . '-' . $option['comment_id'] . '.log', 'a+');
                fwrite($fp, $debug);
                fclose($fp);

                if (isset($json['status']) && $json['status'] == 401) {
                    return ['status' => false, 'code' => 401, 'msg' => '***** *****'];
                } elseif (isset($json['status']) && $json['status'] == 400) {
                    return ['status' => false, 'code' => 400, 'msg' => '*****'];
                } else {
                    return ['status' => false, 'code' => 402, 'msg' => '*****'];
                }

            }

        } else {
            return ['status' => false, 'code' => 403, 'msg' => '*****'];
        }

    }

    /**
     *  Отправляет запрос rucaptcha об успешной прохождении капчи, нужно для подбора кондидатов с наибольшей совместимостью нашего scope reCaptcha
     * @param $taskId
     * @throws GuzzleException
     */
    function sendGoodReport($taskId)
    {
        $client = new Client();
        $client->post('https://rucaptcha.com/res.php?key=' . self::CAPTCHA_API_TOKEN . '&action=reportgood&id=' . $taskId);
    }

    /**
     *  Отправляет запрос rucaptcha об успешной прохождении капчи, нужно для подбора кондидатов с наибольшей совместимостью нашего scope reCaptcha
     * @param $taskId
     * @throws GuzzleException
     */
    function sendBadReport($taskId)
    {
        $client = new Client();
        $client->post('https://rucaptcha.com/res.php?key=' . self::CAPTCHA_API_TOKEN . '&action=reportbad&id=' . $taskId);
    }
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function now()
    {
        $date = new \DateTime('now', new \DateTimeZone($this->timezone));
        return $date->format('d.m.Y H:i:s');
    }


    public function checkTaskAlive()
    {
        $taskFromPullNow = json_decode(file_get_contents($this->path_json . 'pull.json'), true);
        $keys = array_keys($taskFromPullNow);
        $result = $this->pull[0]['task_id'] . $this->pull[0]['comment_id'];



        if (in_array($result, $keys)) {
            return true;
        }


        return false;
    }
}

/**
 * @param $var
 * @param string $filename Имя файла
 * @param string $mode Режим записи
 */
 function logger($var, string $filename = 'logs.log', string $mode = 'w+')
{
    ob_start();
    echo '---------------------' . PHP_EOL;
    echo '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
    echo '---------------------' . PHP_EOL;
    print_r($var);
    echo '---------------------' . PHP_EOL;
    $debug = ob_get_contents();
    ob_end_clean();
    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/logs/' . $filename, $mode);
    fwrite($fp, $debug);
    fclose($fp);
}
?>