<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JsonController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $maxTime = 120;
		set_time_limit($maxTime);
		ini_set('max_execution_time',	  $maxTime);
		ini_set('default_socket_timeout', $maxTime);
		ini_set('max_execution_time',	  $maxTime);
		ini_set('memory_limit',			  '5G');

        $directory         = public_path().'/json_files_3';
        $scanned_directory = array_diff(scandir($directory), array('..', '.'));

        print '<html style="background:#282923; color:#ccc;">';

        $data       = [];
        $manufArr   = [];
        $countryArr = [];
        $headersArr = [];
        $modelArr   = [];
        $tsTableTtl = [];
        $groupsArr  = [];
        $finalArr   = [];
        $docxCount  = 0;

        
        /*
        foreach($scanned_directory as $jsonFileName)
        {
            $filePath = public_path().'/json_files_3/'.$jsonFileName;

            $jsonString  = trim(file_get_contents($filePath));
            $jsonData    = json_decode($jsonString, true);

            $fileName = (string)trim($jsonData['file_name']) ?? '';

            if ($fileName == '211018_ТКП_PRAKT_СРВ1650Р_шаблон.docx')
            {
                dump($jsonData);
                exit;
            }
        }
        exit;
        */

        $docxFilesArr = [];

        foreach($scanned_directory as $jsonFileName)
        {
            $filePath = public_path().'/json_files_3/'.$jsonFileName;

            $jsonString  = trim(file_get_contents($filePath));
            $jsonData    = json_decode($jsonString, true);

            $fileName = (string)trim($jsonData['file_name']) ?? '';

            $fileName = str_replace(':', '/', $fileName);
            $fileName = str_replace("\u{A0}", ' ', $fileName);

            $fileNameLower = mb_strtolower($fileName);

            $excludeFiles =
            [
                'VA2_e___æ__a_¹__3884-MTQ04-02i_¼_a___i_¼___0i-MF_Plus__ru-RU_-8300_N____Ð__detailed.json',
                'Временное_Руководство-_KV600АТС_С_с_правками_для_Лукинского_А__detailed.json',
                'Руководство-_KV600АТС_С_detailed.json',
                'Руководство_Мех__часть_VMC_2_detailed.json',
                'Руководство_Мех__часть_VMC_detailed.json',
                'Руководство_Мех__часть_VR1060_detailed.json',
                'Руководство_Эл__часть_VMC_2_detailed.json',
                'Руководство_Эл__часть_VMC_detailed.json',
                'Руководство_Эл__часть_VR1060_detailed.json',
                'Руководство_пользователя__FERZ_L1030_detailed.json',
                'Сервис_инженер_ЖиМа_Должностная_инструкция_окончат__detailed.json',
                'Сервис_инженер_ЖиМа_Должностная_инструкция_окончат_правки_видны__detailed.json',
            ];

            //dump($jsonFileName);

            if (file_exists($filePath) && !mb_strstr($fileNameLower, 'договор') && mb_strstr($fileNameLower, '.docx') && !in_array($jsonFileName, $excludeFiles))
            {
                $docxCount++;

                if (!in_array($fileName, $docxFilesArr)) $docxFilesArr[] = $fileName;

                $headers = (array)$jsonData['main_headers'] ?? [];

                $manufacturer = (array)$jsonData['manufacturer_info'] ?? [];

                $manufacturerName    = $manufacturer['company_name'] ?? '';
                $manufacturerCountry = $manufacturer['country']      ?? '';
    
                $manufacturerName = preg_replace('/[^\p{Cyrillic}a-zA-Z\p{Han}0-9\.,\-\s]/u', '', $manufacturerName);

                $manufacturerName = str_replace('   ', ' ', $manufacturerName);
                $manufacturerName = str_replace('&nbsp;',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);

                $manufacturerName = str_replace('Компания', '', $manufacturerName);
                $manufacturerName = str_replace('компания', '', $manufacturerName);
                $manufacturerName = str_replace('ПРОИЗВОДИТЕЛЬ ЗАВОД - ПРОИЗВОДИТЕЛЬ', '', $manufacturerName);
                $manufacturerName = str_replace('ПРОИЗВОДСТВО КОМПАНИИ', '', $manufacturerName);
                $manufacturerName = str_replace('производства компании', '', $manufacturerName);
                $manufacturerName = str_replace('Общие сведения о производителе', '', $manufacturerName);
                $manufacturerName = str_replace('производства', '', $manufacturerName);
                $manufacturerName = str_replace('КОМПАНИЯ', '', $manufacturerName);
                $manufacturerName = str_replace('в России', '', $manufacturerName);
                $manufacturerName = str_replace('БАРУС ОБОРУДОВАНИЕ стала официальным сервисным и торговым представителем', '', $manufacturerName);
                $manufacturerName = str_replace('ПРОИЗВОДСТВО', '', $manufacturerName);
                $manufacturerName = str_replace('КОМПАНИИ', '', $manufacturerName);
                $manufacturerName = str_replace('Бренд FERZ представляет завод - производитель', '', $manufacturerName);
                $manufacturerName = str_replace('является эксклюзивным дилером Производителя', '', $manufacturerName);
                $manufacturerName = str_replace('Общие сведения о производителе', '', $manufacturerName);
                $manufacturerName = str_replace('Общие сведения о производителе EXTRON - это всемирный бренд Yih Chuan Machinery Industry', '', $manufacturerName);
                $manufacturerName = str_replace('была', '', $manufacturerName);
                $manufacturerName = str_replace('EXTRON - это всемирный бренд', '', $manufacturerName);
                $manufacturerName = str_replace('weber . ru', '', $manufacturerName);
                $manufacturerName = str_replace('FERZ', '', $manufacturerName);

                $manufacturerName = preg_replace('/[^a-zA-Z0-9\.,\-\s]/', '', $manufacturerName);
                $manufacturerName = preg_replace('/[^a-zA-Z0-9\.,\-\s]/', '', $manufacturerName);
                $manufacturerName = preg_replace('/^[\s\.,\-]+/', '', $manufacturerName);
                $manufacturerName = preg_replace('/[\s\.,\-]+$/', '', $manufacturerName);
                $manufacturerName = preg_replace('/^[A-Za-z]\s/', '', $manufacturerName);

                $manufacturerName = str_replace('- ', '-', $manufacturerName);
                $manufacturerName = str_replace('Heavy Duty Precision Lathe', '', $manufacturerName);
                $manufacturerName = str_replace('Siemens S', '', $manufacturerName);
                $manufacturerName = str_replace('DAH LIH Machinery Industry', 'DAH LIH Machinery Industry Co', $manufacturerName);
                $manufacturerName = str_replace('Co Co', 'Co', $manufacturerName);
                $manufacturerName = str_replace('   ', ' ', $manufacturerName);
                $manufacturerName = str_replace('&nbsp;',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);
                $manufacturerName = str_replace('  ',  ' ', $manufacturerName);

                $manufacturerName = trim($manufacturerName);

                if (preg_match('/^[\p{Cyrillic}\s]+$/u', $manufacturerName))
                {
                    $manufacturerName = '';
                }

                $manufacturerLowerName = trim(mb_strtolower($manufacturerName));

                if (in_array($manufacturerLowerName, ['в россии']) || mb_strlen($manufacturerLowerName) > 100 || mb_strlen($manufacturerLowerName) < 3 || mb_strstr($manufacturerLowerName, 'fanuc'))
                {
                    $manufacturerName = '';
                }

                if ($manufacturerName <> '')
                {
                    $c = ($manufacturerCountry <> '') ? ' ('.$manufacturerCountry.')' : '';
                    $manufArr[mb_strtoupper($manufacturerName)] = $manufacturerName.$c;
                }

                $tablesAll = (array)$jsonData['tables'] ?? [];

                $tables = ['technical_specifications' => [], 'pricing' => []];

                foreach($tablesAll as $table)
                {
                    $tableType = (string)trim($table['table_type']);
                    if (isset($tables[$tableType]))
                    {
                        $tables[$tableType][] = (array)$table['rows'];
                    }
                }

                if (count($headers) > 0 && count($tables['technical_specifications']) > 0 && count($tables['pricing']) > 0)
                {
                    $newHeaders = [];

                    $isStartWord = false;
                    $exclude = false;

                    $model = [];

                    foreach($headers as $i => $header)
                    {
                        $header = trim($header);

                        $header = $this->removeDuplicatePhrases($header);

                        $headerUpper = mb_strtoupper($header);

                        if (mb_strstr($headerUpper, 'ДОГОВОР'))
                        {
                            $header = '';
                            unset($headers[$i]);
                        }

                        if (mb_strstr($headerUpper, 'SPECIFICATION') || mb_strstr($headerUpper, 'TAIWAN TAKISAWA TECHNOLOGY CO., LTD. ( ТАЙВАНЬ )') || mb_strstr($headerUpper, '报 价 单 及 技 术 协 议') || (count($headers) == 1 && mb_strstr($headerUpper, 'ТЕХНИКО-КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИ Е'))  || (count($headers) == 1 && mb_strstr($headerUpper, 'Т ЕХНИКО-КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ')) || mb_strstr($header, 'To Contract'))
                        {
                            $exclude = true;
                        }

                        if (mb_strstr($headerUpper, 'ТЕХНИКО-КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ') || mb_strstr($headerUpper, 'КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ') || mb_strstr($headerUpper, 'ТЕХНИ ЧЕСКОЕ ПРЕДЛОЖЕНИЕ') || mb_strstr($headerUpper, 'ТЕХНИЧЕСКОЕ ПРЕДЛОЖЕНИЕ') || mb_strstr($headerUpper, 'ГОРИЗОНТ') || mb_strstr($headerUpper, 'ВЕРТИКАЛ') || ($i == 0 && mb_strstr($headerUpper, 'МОДЕЛЬ')) || mb_strstr($headerUpper, 'ОКАРНЫЙ') || mb_strstr($headerUpper, 'SkD') || mb_strstr($headerUpper, 'СТАНОК') || mb_strstr($headerUpper, 'ТОКАРН') || mb_strstr($headerUpper, 'ПРЕДЛОЖЕНИЕ НА') || mb_strstr($headerUpper, 'TAISUN ACCURATE 108Y'))
                        {
                            $isStartWord = true;
                        }
                        

                        $els = explode('ТЕХНИКО-КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ', $header);

                        if (count($els) > 1)
                        {
                            $header = [];
                            $z = 0;
                            foreach($els as $el)
                            {
                                if ($z > 0)
                                {
                                    $header[] = $el;
                                }
                                $z++;
                            }
                            $header = trim(implode(' ', $header));
                        }

                        $wrds =
                        [
                            'Модель:',
                            'МОДЕЛЬ:',
                            'Модели :',
                            
                            'с ЧПУ модели',
                            'модели ',
                            'МОДЕЛИ ',

                            'обрабатывающий центр с ЧПУ модели',
                            'обрабатывающий центр с ЧПУ',
                            'ОБРАБАТЫВАЮЩИЙ ЦЕНТР С ЧПУ',
                            'ОБРАБАТЫВАЮЩИЙ центр с ЧПУ',
                            'центр с ЧПУ',
                            'ЦЕНТР С ЧПУ',
                            'станок с ЧПУ',
                            'обрабатывающий центр м одел и',
                            'обрабатывающий центр колонного типа',
                            'обрабатывающий центр портального типа',

                            'обрабатывающий центр модели',

                            'обрабатывающий центр',
                            'ОБРАБАТЫВАЮЩИЙ ЦЕНТР',

                            'с ЧПУ',
                            'на станке',
                            'модель ',
                            'модел ей ',
                            'моделей ',
                            'Модел ь',
                            'Токарный автомат продольного точения',
                            'двухколонного типа',
                            'обрабатывающиЙ центр',
                            'ОБРАБАТЫВАЮЩИЙ ЦЕНТР',
                            'модел и ',
                            'ОБРАБАТЫВАЮЩЕГО ЦЕНТРА',
                            'МОДЕЛЬ ',
                            'обра батывающий центр',
                            'Модель ',
                            'м одел и ',
                            'Вертикальный токарный станок ',
                            'Вертикально-фрезерные станки серии',
                            'станок серии',
                            'характеристики СТАНКА',
                            'характеристики станка',
                            'ТОКАРНЫЙ СТАНОК С ЧПУ',
                            'Резьбонакатной станок',
                            'РЕЗЬБОНАКАТНОЙ СТАНОК',
                            'ПРУЖИНОНАВИВОЧНЫЙ СТАНОК',
                            'FERZ',
                            'КРУГЛОШЛИФОВАЛЬНЫЙ СТАНОК С РУЧНЫМ УПРАВЛЕНИЕМ',
                            'QUCIK-TECH',
                            'BODOR',
                            'Everising',
                            'технико-коммерческое предложение НА ЭЛЕКТРОЭРОЗИОННЫЙ ПРОШИВНОЙ СТАНОК',
                            'SEMAT',
                            'SEM A T',
                            'Вертикальный токарный станок',
                            'SkD3025',
                            'Mitsubishi',
                            'SkD2520',
                            'TAISUN ACCURATE 108Y',
                            'TAISUN ACCURATE 108M',
                            'H-560 HANC',
                            'H-260HB',
                            'ОБРАБАТЫВАЮЩ ИЙ ЦЕНТР',
                            'КРУГЛОШЛИФОВАЛЬНЫЙ СТАНОК',
                            'ПЛОСКОШЛИФОВАЛЬНЫЙ СТАНОК',
                            'ШИРОКОУНИВЕРСАЛЬНЫЙ ФРЕЗЕРНЫЙ СТАНОК NSM-T',
                            'TORNOS',
                            'TAISUN',
                            'PRAKT',
                            'TSUGAMI',
                            'Quick-Tech i-60 TWIN',
                            'Универсальный токарно-винторезный станок',
                            'SkD 4030',
                            'MEGA TR-30 T',
                            'GUOWANG GW-92F',
                            'KURAKI AKB-11',
                            'H-2 60HB NC EVERISING',
                            'ЛИСТОГИБОЧНЫЙ ПРЕСС',
                            'УНИВЕРСАЛЬНЫЙ ТОКАРНЫЙ СТАНОК',
                            'GA-43',
                            'GA-53',
                            'RICO PRCN 2070',
                            'JINN FA JSL-20RB',
                            'GS-3015',
                            'Технико-коммерческое предложение на поставку',
                            'Коммерческое предложение на ленточнопильный автоматический ст а нок по металлу',
                            'Коммерческое предложение на ленточнопильный автоматический станок по металлу',
                            'Горизонтальны е токарно-револьверные станки с горизонтальной станиной с числовым программным управлением серии HA-1400/1600 /2000 производства компании GOODWAY Machine Cor p . (Тайвань)',
                            'Предложение на комплекс лазерного раскроя металла оптоволоконный',
                            'Токарныи ̆ обрабатывающии ̆ центр',
                            'ГОРИЗОНТАЛЬНЫЙ ФРЕЗЕРНЫЙ ОБРАБАТЫВАЮЩИЙ',
                        ];

                        foreach($wrds as $wrd)
                        {
                            $els = explode($wrd, $header);

                            if (count($model) == 0 && mb_strstr($header, $wrd))
                            {
                                if ($wrd == 'TAISUN ACCURATE 108Y')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'TAISUN ACCURATE 108M')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'H-560 HANC')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'SkD3025')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'SkD2520')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'SkD 4030')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'H-260HB')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'ШИРОКОУНИВЕРСАЛЬНЫЙ ФРЕЗЕРНЫЙ СТАНОК NSM-T')
                                {
                                    $model[] = 'NSM-T';
                                }
                                elseif ($wrd == 'MEGA TR-30 T')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'GUOWANG GW-92F')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'KURAKI AKB-11')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'H-2 60HB NC EVERISING')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'GA-43')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'GA-53')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'RICO PRCN 2070')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'JINN FA JSL-20RB')
                                {
                                    $model[] = $wrd;
                                }
                                elseif ($wrd == 'GS-3015')
                                {
                                    $model[] = $wrd;
                                }
                                elseif (count($els) > 1)
                                {
                                    $model = [];
                                    $z     = 0;
                                    foreach($els as $el)
                                    {
                                        if ($z > 0)
                                        {
                                            $mdl = trim($el);
                                            if (!in_array($mdl, $model))
                                            {
                                                if ($wrd == 'FERZ') $mdl = 'FERZ '.$mdl;
                                                if ($wrd == 'QUCIK-TECH') $mdl = 'QUCIK-TECH '.$mdl;
                                                if ($wrd == 'BODOR') $mdl = 'BODOR '.$mdl;
                                                if ($wrd == 'Everising') $mdl = 'Everising '.$mdl;
                                                if ($wrd == 'SEMAT') $mdl = 'SEMAT '.$mdl;
                                                if ($wrd == 'SEM A T') $mdl = 'SEM A T '.$mdl;
                                                if ($wrd == 'Mitsubishi') $mdl = 'Mitsubishi '.$mdl;
                                                if ($wrd == 'TORNOS') $mdl = 'TORNOS '.$mdl;
                                                if ($wrd == 'TAISUN') $mdl = 'TAISUN '.$mdl;
                                                if ($wrd == 'PRAKT') $mdl = 'PRAKT '.$mdl;
                                                if ($wrd == 'TSUGAMI') $mdl = 'TSUGAMI '.$mdl;
                                                if ($wrd == 'Quick-Tech i-60 TWIN') $mdl = 'Quick-Tech i-60 TWIN '.$mdl;

                                                $mdl = trim($mdl);

                                                if (mb_strlen($mdl) >= 2)
                                                {
                                                    $model[] = $mdl;
                                                }
                                            }
                                        }
                                        $z++;
                                    }
                                }

                                if(count($model) == 0 && isset($headers[$i+1]))
                                {
                                    $model[] = $headers[$i+1];
                                }
                            }
                        }

                        if (!$exclude && count($model) > 0)
                        {
                            $wrds =
                            [
                                'производства Push Ningjiang Machine Tool Co ., LTD ( Китай ) Прецизионный зубофрезерный',
                                ', имеющий 7 управляемых осей для обработки цилиндрических зубчатых колес с модулем до 3 мм и наружным диаметром до 100 мм',
                                ' ( № 654)  GOODWAY Machine Cor p . ( Тайвань )',
                                'производства компании GOODWAY Machine Corp. ( Тайвань )',
                                'производства компании “ DAH LIH MACHNERY INDUSTRY CO., LTD ” , Тайвань .',
                                'в комплектации №1',
                                'Общий вид станка (модель ',
                                'Общий вид станка ( модель',
                                'для производства пружин сжатия . Модель ',
                                'фирмы EVERISING',
                                'производства ком пании GOODWAY Machine Cor p . ( Тайвань )',
                                'производства компании DAHLIH MACHNERY INDUSTRY CO., LTD, Тайвань.',
                                'производства компании DAH LIH MACHNERY INDUSTRY CO., LTD.',
                                'производства компании You Ji Machine Industrial Co., Ltd., Тайвань',
                                'C производства компании You Ji Machine Industrial Co ., Ltd ., Тайвань',
                                'а с ЧПУ ',
                                'GoodWay',
                                'модели',
                                'производства компании DAH LIH MACHNERY INDUSTRY CO., LTD, Тайвань.',
                                'производства компании',
                                'производства Push Ningjiang Machine Tool Co ., LTD ( Китай) Прецизионный зубофрезерный',
                                ', имеющий 7 управляемых осей для обработки цилиндрических зубчатых колес с модулем до 3мм и наружным диаметром до 100мм',
                                'Tianjin No.1 Machine Tool Works (TMTW) , Китай .',
                                ',  GOODWAY (Тайвань)',
                                'AWEA Mechantronic Ltd.',
                                'Технические характеристики станка',
                                ', имеющий 7 управляемых осей для обработки цилиндрических зубчатых колес любых видов с модулем до 4 мм и наружным диаметром до 200 мм',
                                'с установленный дополнительным фильтром для масляного тумана',
                                'AWEA Mechantronic ( Suzhou ) Ltd . (Кита й)',
                                'версии NC прои з водства компании Paragon Machinery Co ., Ltd , Тайвань',
                                'фирмы EVERI S ING',
                                'Everising',
                                'версии NC  Paragon Machinery Co ., Ltd , Тайвань',
                                'Pee Wee Kaltwalz (Германия). Предложение подготовлено с учетом требований и технологических задач вашего предприятия .',
                                'Резьбона катной станок',
                                'Станок рекомендуется к эксплуатации в условиях как единичного, так и серийного производства.',
                                ', имеющий 6 управляемых осей для обработки цилиндрических зубчатых колес любых видов с модулем до 3 мм и наружным диаметром до 166 мм',
                                'AWEA Mechantronic Ltd .',
                                ' ,  GOODWAY ( Тайвань)',
                                'You Ji Machine Industrial Co ., Ltd ., Тайвань',
                                ' , c истемMitsubishi M 80',
                                'в комплект ации №3',
                                'TAIWAN TAKISAWA TECHNOLOGY CO., LTD. ( ТАЙВАНЬ )',
                                'С Y ОСЬЮ',
                                'в базовой комплектации, в комплекте:',
                                'Отличное расположение инструмента и большая рабочая зона делают данный станок максимально универсальным.',
                                '7~2200 (low-range 7~135, mid-range 30~550, high-end 110~2200)',
                                ' ( № 655)  GOODWAY Machine Cor p . ( Тайвань )',
                                ' ( № 728)  GOODWAY Machine Cor p . ( Тайвань )',
                                'отличаются только инструментарием ( количеством приводных / неприводных инструментов ).',
                                'ЧПУ',
                                'С ПРОТИВОШПИНДЕЛЕМ И Y ОСЬЮ',
                                'КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ 8',
                                'поворотных столов .',
                                'Шпиндель для  AH06/09/12',
                                'производства ко м пании Paragon Machinery Co.,Ltd , Тайвань',
                                'с  (поставка со склада в Москве)',
                                'с серводвигателем и прямым приводом усилием 16 0 тонн фирм ы SEYI .',
                                'TSUGAMI , так как это может привести к серьезным травмам. Внешний вид и технические характеристики этого центра могут быть изменены без предварительного уведомления. При передаче или продаже центра не забудьте приложить к нему данное руководство и входящие в комплект учебные пособия. При обращении с центром и отработанной смазочно-охлаждающей жидкостью соблюдайте правила национальных регуляторных органов.',
                                'модел и',
                                'Система： ',
                                'МОДЕЛЬ: ',
                                '2.7. Работа манипулятора ATC 26',
                                '2.7. Работа манипулятора ATC 25',
                                'LK MACHINERY МОДЕЛЬ',
                                ' (№ 728 )  GOODWAY Machine Cor p . (Та й вань)',
                                'GOODWAY (SUZHOU) Machine Corp . ( Китай )',
                                'с функцией фрезерования и осью Y TAISUN SEIKI S UPER Turn-230 MY',
                                'SEMAT , включая цифровой генератор, систему К, гидростанцию Sk4030 P , хода X=400 мм, Y=300 мм, Z=300 мм',
                                'Tianjin No .1 Machine Tool Works ( TMTW ) , Китай .',
                                'Технико-коммерческое предложение',
                                'с двумя паллетами',
                                '(с приводной револьверной головкой, противошпинделем )',
                                '( № 654)  GOODWAY Machine Cor p . ( Тайвань )',
                                'Шпиндель для серии AH06/09/12',
                                '-Система автоматической централ изованн ой смазки-Малошумный гидроагрегат',
                                'TSUGAMI VA2. Данное руководство является основным руководством по использованию , вводу в эксплуатацию и техническому обслуживанию центра VA2. К центру также прилагаются следующие инструкции по эксплуатации. Перед началом эксплуатации центра, пожалуйста, внимательно прочтите содержание следующего руководства. Серия FANUC 0i-MF Plus Руководство по эксплуатации',
                                ': Fanuc 0i-MF Plus , 10.4 ” color LCD , Manual Guide i , AICC II',
                                ', имеющий 6 управляемых осей для обработки цилиндрических зубчатых колес любых видов с модулем до 3 мм и наружным диаметром до 166мм',
                                ', имеющий 7 управляемых осей для обработки цилиндрических зубчатых колес любых видов с модулем до 4 мм и наружным диаметром до 200мм',
                                '18 x 3',
                                '2.7.Работа манипулятора ATC25',
                                '2.7.Работа манипулятора ATC26',
                                'GA-2000 / GA-2600 / GA-2800  GOODWAY Machine Corp. (Тайвань)',
                            ];
                            
                            foreach($model as $z => $mdl)
                            {
                                $mdl = str_replace($wrds, '', $mdl);

                                if ($fileName == '201015_ТКП_ONE + TSSS_Велмаш-С.docx')
                                {
                                    $exclude = false;
                                    $model   = ['ONE PLC 5'];
                                }
                                elseif ($fileName == '2016.11.16 AF650.docx')
                                {
                                    $exclude = false;
                                    $model   = ['AF650'];
                                }
                                
                                elseif ($mdl == 'JL-V 280 Вертикально-горизонтальные фрезерные станки серии JL-VH 32 0 Вертикально-горизонтальные фрезерные станки с серии JL-VH 15 с поворотной головкой вертикального шпинделя')
                                {
                                    $model = ['JL-V 280', 'JL-VH 320', 'JL-VH 15'];
                                }
                                elseif($mdl == 'CYA-103 L , CYA-103 BL .')
                                {
                                    $model = ['CYA-103 L', 'CYA-103 BL'];
                                }
                                elseif($mdl == 'GA-2000 / GA-2600 / GA-2800 производства компании GOODWAY Machine Cor p . (Тайвань)')
                                {
                                    $model = ['GA-2000', 'GA-2600', 'GA-2800'];
                                }
                                elseif($mdl == 'GMT-2600ST GMT-2800ST')
                                {
                                    $mdl = ['GMT-2600ST', 'GMT-2800ST'];
                                }
                                elseif($mdl == 'GA-3 000 / GA-33 00 / GA-36 00 производства компании GOODWAY Machine Cor p . (Тайвань)' || $mdl == 'GA-3 000 / GA-33 00 / GA-36 00  GOODWAY Machine Cor p . (Тайвань)')
                                {
                                    $model = ['GA-3000', 'GA-3300', 'GA-3600'];
                                }
                                elseif($mdl == 'GA-2600-300/ / GA-2600 / GA-2 600 L  GOODWAY Machine Cor p . (Тайвань)')
                                {
                                    $model = ['GA-2600-300', 'GA-2600', 'GA-2 600 L'];
                                }
                                elseif($mdl == 'SA 20 B :')
                                {
                                    $model = ['SA20B'];
                                }
                                elseif($mdl == 'Система  FANUC Series 0i-TD Ввод / вывод данных в С с использованием карты памяти стандарта PCMCIA , RS-232 C')
                                {
                                    $model = ['FANUC Series 0i-TD'];
                                }
                                elseif($mdl == 'G LS-1500 L Y ; G LS-2000 L Y  GOODWAY Machine Cor p . (Тайвань)')
                                {
                                    $model = ['G LS-1500 L Y', 'G LS-2000 L Y'];
                                }
                                elseif($mdl == ': L1030')
                                {
                                    $model = ['L1030'];
                                }
                                elseif($mdl == 'Модель « LP-6021 »  « AWEA » ( Тайвань )')
                                {
                                    $model = ['LP-6021'];
                                }
                                elseif($mdl == 'GA-2000 / GA-2600 / GA-2800  GOODWAY Machine Cor p . (Тайвань)')
                                {
                                    $model = ['GA-2000', 'GA-2600', 'GA-2800'];
                                }
                                elseif($mdl == 'GLS-2800 ; GLS-3300 ,')
                                {
                                    $model = ['GLS-2800', 'GLS-3300'];
                                }
                                elseif($mdl == 'SW-20, SW-32, SW-42.')
                                {
                                    $model = ['SW-20', 'SW-32', 'SW-42'];
                                }
                                elseif($mdl == 'HA-1400L2-L10 HA-1600L2-L10 HA-2000L2-L10')
                                {
                                    $model = ['HA-1400L2-L10', 'HA-1600L2-L10', 'HA-2000L2-L10'];
                                }
                                elseif($mdl == 'GA-2000 / GA-2600 / GA-2800  GOODWAY Machine Corp. (Тайвань)')
                                {
                                    $model = ['GA-2000', 'GA-2600', 'GA-2800'];
                                }
                                elseif($mdl == 'GA-2000-300// GA-2000 // GA-2000L  GOODWAY Machine Corp. (Тайвань)')
                                {
                                    $model = ['GA-2000-300', 'GA-2000', 'GA-2000L'];
                                }
                                elseif($mdl == 'GA-2600-300// GA-2600 // GA-2600L  GOODWAY Machine Corp. (Тайвань)')
                                {
                                    $model = ['GA-2600-300', 'GA-2600', 'GA-2600L'];
                                }
                                elseif($mdl == 'GA-3000 / GA-3300 / GA-3600  GOODWAY Machine Corp. (Тайвань)')
                                {
                                    $model = ['GA-3000', 'GA-3300', 'GA-3600'];
                                }
                                elseif($mdl == 'GA-3000 // 3000-900// L')
                                {
                                    $model = ['GA-3000', '3000-900 // L'];
                                }
                                elseif($mdl == 'GLS-1500 / GLS-1500L; GLS-2000 / GLS-2000L  GOODWAY Machine Corp. (Тайвань)')
                                {
                                    $model = ['GLS-1500', 'GLS-1500L', 'GLS-2000', 'GLS-2000L'];
                                }
                                elseif($mdl == 'GLS-1500LY; GLS-2000LY  GOODWAY Machine Corp. (Тайвань)')
                                {
                                    $model = ['GLS-1500LY', 'GLS-2000LY'];
                                }
                                elseif($mdl == 'GLS-2800 ; GLS-3300,')
                                {
                                    $model = ['GLS-2800', 'GLS-3300'];
                                }
                                elseif($mdl == 'CYA-103L, CYA-103BL.')
                                {
                                    $model = ['CYA-103L', 'CYA-103BL'];
                                }
                                elseif($mdl == 'zzzzz')
                                {
                                    $model = ['', '', ''];
                                }
                                elseif($mdl == 'zzzzz')
                                {
                                    $model = ['', '', ''];
                                }
                                elseif($mdl == 'zzzzz')
                                {
                                    $model = ['', '', ''];
                                }
                                elseif($mdl == 'zzzzz')
                                {
                                    $model = ['', '', ''];
                                }
                                elseif($mdl == 'zzzzz')
                                {
                                    $model = ['', '', ''];
                                }
                                elseif($mdl == 'zzzzz')
                                {
                                    $model = ['', '', ''];
                                }
                                else
                                {
                                    $mdl = str_replace('AF-650  AWEA Mechantronic (Suzhou) Ltd. (Китай)', 'AF-650', $mdl);
                                    $mdl = str_replace('FERZ-FS10M(VDI40)', 'FERZ-FS10M (VDI40)', $mdl);
                                    $mdl = str_replace('FERZ-FS8M(VDI40)', 'FERZ-FS8M (VDI40)', $mdl);
                                    $mdl = str_replace('производства Push Ningjiang Machine Tool Co., LTD (Китай) Прецизионный зубофрезерный', '', $mdl);
                                    $mdl = str_replace('GOODWAY (SUZHOU) Machine Corp. (Китай)', '', $mdl);
                                    $mdl = str_replace('Goodway GA-2600M (№654)  GOODWAY Machine Corp. (Тайвань)', 'GA-2600M', $mdl);
                                    $mdl = str_replace('Goodway GLS-2000LM (№655)  GOODWAY Machine Corp. (Тайвань)', 'GLS-2000LM', $mdl);
                                    $mdl = str_replace('Goodway GLS-2000LM (№728)  GOODWAY Machine Corp. (Тайвань)', 'GLS-2000LM', $mdl);
                                    $mdl = str_replace('Goodway SW-20MXS', 'SW-20MXS', $mdl);
                                    $mdl = str_replace('GOODWAY Machine Corp. (Тайвань) Станки серии GS-200 разрабатывались с целью удовлетворения самых высоких требований современного производства.', '', $mdl);
                                    $mdl = str_replace('S/N 91B1387', '', $mdl);
                                    $mdl = str_replace('GOODWAY Machine Corp. (Тайвань)', '', $mdl);
                                    $mdl = str_replace('Paragon Machinery Co.,Ltd, Тайвань', '', $mdl);
                                    $mdl = str_replace('Вертикально-горизонтальные фрезерные станки серии JL-VH320 Вертикально-горизонтальные фрезерные станки с серии JL-VH15 с поворотной головкой вертикального шпинделя', '', $mdl);
                                    $mdl = str_replace('“DAH LIH MACHNERY INDUSTRY CO., LTD”, Тайвань.', '', $mdl);
                                    $mdl = str_replace('(№ 277)', '', $mdl);
                                    $mdl = str_replace('6.0 кВт', '', $mdl);
                                    $mdl = str_replace('в комплектации №3', '', $mdl);
                                    $mdl = str_replace(', cистемMitsubishi M80', '', $mdl);
                                    $mdl = str_replace('Quick-Tech', 'QUICK-TECH', $mdl);
                                    $mdl = str_replace('Quick-tech t8 hybrid y', 'QUCIK-TECH T8-HYBRID Y', $mdl);
                                    $mdl = str_replace('с серводвигателем и прямым приводом усилием 160 тонн фирмы SEYI .', '', $mdl);
                                    $mdl = str_replace(', включая цифровой генератор, систему К, гидростанцию Sk4030P, хода X=400 мм, Y=300 мм, Z=300 мм', 'Sk4030P', $mdl);
                                    $mdl = str_replace('S480x1000)', 'S480 x 1000', $mdl);
                                    $mdl = str_replace('TAIWAN TAKISAWA TECHNOLOGY CO., LTD. (ТАЙВАНЬ)', 'TAKISAWA', $mdl);
                                    $mdl = str_replace('C Y ОСЬЮ', '', $mdl);
                                    $mdl = str_replace('(SIEMENS)', '', $mdl);
                                    $mdl = str_replace('Токарно-фрезерный', '', $mdl);
                                    $mdl = str_replace('TSUGAMI VA2. Данное руководство является основным руководством по использованию, вводу в эксплуатацию и техническому обслуживанию центра VA2. К центру также прилагаются следующие инструкции по эксплуатации. Перед началом эксплуатации центра, пожалуйста, внимательно прочтите содержание следующего руководства. Серия FANUC 0i-MF Plus Руководство по эксплуатации', 'TSUGAMI VA2', $mdl);
                                    $mdl = str_replace('Pee Wee Kaltwalz (Германия). Предложение подготовлено с учетом требований и технологических задач вашего предприятия.', '', $mdl);
                                    $mdl = str_replace('UPW 10', 'UPW-10', $mdl);
                                    $mdl = str_replace('V480x1000)', 'V480x1000', $mdl);
                                    $mdl = str_replace('V560x2000)', 'V560x2000', $mdl);
                                    $mdl = str_replace('VMC1000II/Р', 'VMC1000 II/Р', $mdl);
                                    $mdl = str_replace('VMC1200II/Р', 'VMC1200 II/Р', $mdl);
                                    $mdl = str_replace('Tianjin No.1 Machine Tool Works (TMTW), Китай.', '', $mdl);
                                    $mdl = str_replace('(с приводной револьверной головкой, противошпинделем)', '', $mdl);
                                    $mdl = str_replace('для производства пружин сжатия. Модель ', '', $mdl);
                                    $mdl = str_replace('КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ8', '', $mdl);
                                    $mdl = str_replace('Модель «LP-6021»  «AWEA» (Тайвань)', 'LP-6021', $mdl);
                                    $mdl = str_replace('отличаются только инструментарием (количеством приводных/неприводных инструментов).', '', $mdl);
                                    $mdl = str_replace('поворотных столов.', '', $mdl);
                                    $mdl = str_replace('ПОРТАЛЬНОГО ТИПА ', '', $mdl);
                                    $mdl = str_replace('Резьбонакатной станок', '', $mdl);
                                    $mdl = str_replace('с функцией фрезерования и осью Y TAISUN SEIKI SUPER Turn-230MY', 'TAISUN SEIKI SUPER Turn-230MY', $mdl);
                                    $mdl = str_replace('серии GU-3250 версии NC', 'GU-3250 (NC)', $mdl);
                                    $mdl = str_replace('серии GU-3275 версии NC', 'GU-3275 (NC)', $mdl);
                                    $mdl = str_replace('Система：Mitsubishi M80', 'Mitsubishi M80', $mdl);
                                    $mdl = str_replace('GS-2000 YS', 'GS-2000YS', $mdl);
                                    $mdl = str_replace('mv1350 fanuc 0imf plus', 'MV-1350', $mdl);
                                    $mdl = str_replace('SPS Siemens S7 с дисплеем на русском и немецком языке (ОПЦИЯ) Все станки оборудованы наклонными шпинделями и тем самым подходят для накатывания как методом радиальной, так и осевой подачи.', 'SPS Siemens S7', $mdl);
                                    
                                    /*
                                    $mdl = preg_replace('/(\d)\s+(\d)/', '$1$2', $mdl);
                                    $mdl = preg_replace('/(\d)\s+(\d)/', '$1$2', $mdl);

                                    $mdl = str_replace('серии G U', 'GU', $mdl);
                                    $mdl = str_replace('L Y S', 'LYS', $mdl);
                                    $mdl = str_replace('L 2 Y', 'L2Y', $mdl);
                                    $mdl = str_replace('Y B S', 'YBS', $mdl);
                                    $mdl = str_replace('G S', 'GS', $mdl);
                                    $mdl = str_replace('D M', 'DM', $mdl);
                                    $mdl = str_replace('Y K', 'YK', $mdl);
                                    $mdl = str_replace('S D ', 'SD ', $mdl);
                                    $mdl = str_replace('C FV', 'CFV ', $mdl);
                                    $mdl = str_replace('G U', 'GU', $mdl);
                                    $mdl = str_replace('M Е', 'MЕ', $mdl);
                                    $mdl = str_replace('T R', 'TR', $mdl);

                                    $mdl = preg_replace('/(\d)\s+\/(\d)/', '$1/$2', $mdl);
                                    $mdl = preg_replace('/^([A-Za-z])\s+([A-Za-z])(?=\s|$)/', '$1$2', $mdl);
                                    $mdl = preg_replace('/^([A-Za-z])\s+([A-Za-z])(?=\s|$)/', '$1$2', $mdl);
                                    $mdl = preg_replace('/(?<=\s|^)([A-Za-z])\s+([A-Za-z])$/', '$1$2', $mdl);
                                    $mdl = preg_replace('/(?<=\s|^)([A-Za-z])\s+([A-Za-z])$/', '$1$2', $mdl);

                                    $mdl = str_replace('G LS', 'GLS', $mdl);
                                    $mdl = str_replace('L G 1 S 2', 'LG1S2', $mdl);
                                    $mdl = str_replace('V 480x1000 )', 'V 480x1000', $mdl);
                                    $mdl = str_replace('L 660x3300)', 'L 660x3300', $mdl);
                                    $mdl = str_replace('G TW', 'GTW', $mdl);

                                    $mdl = preg_replace('/(?<!\w)\s*([A-Za-z])\s*(?!\w)/', '$1', $mdl);

                                    $mdl = str_replace(' - ', '-', $mdl);
                                    $mdl = str_replace(' – ', '-', $mdl);
                                    $mdl = str_replace('( ', '(', $mdl);
                                    $mdl = str_replace(' )', ')', $mdl);

                                    $mdl = preg_replace('/^([A-Za-zА-Яа-я])\s+(?=\w)/u', '$1', $mdl);
                                    $mdl = preg_replace('/(?<=\w)\s+([A-Za-zА-Яа-я]{1,2})$/u', '$1', $mdl);

                                    $mdl = str_replace('T B-', 'TB-', $mdl);
                                    $mdl = str_replace('T D-', 'TD-', $mdl);
                                    $mdl = str_replace(' х ', 'х', $mdl);
                                    $mdl = str_replace(' x ', 'x', $mdl);
                                    $mdl = str_replace(' x', 'x', $mdl);
                                    $mdl = str_replace('CS-H ', 'CS-H', $mdl);
                                    $mdl = str_replace('CS-U ', 'CS-U', $mdl);
                                    $mdl = str_replace('(№ 277) ', '', $mdl);
                                    $mdl = str_replace('GM S', 'GMS', $mdl);
                                    $mdl = str_replace('V560x2000)', 'V560x2000', $mdl);
                                    $mdl = str_replace('+ ', '+', $mdl);
                                    $mdl = str_replace(' +', '+', $mdl);
                                    $mdl = str_replace('G 5', 'G5', $mdl);
                                    
                                    $mdl = preg_replace('/(?<![A-Za-zА-Яа-я])([A-Za-zА-Яа-я]{2,3})\s+(?=\d)/u', '$1', $mdl);
                                    $mdl = preg_replace('/(\d)\s+([A-Za-zА-Яа-я]{1,3})$/u', '$1$2', $mdl);

                                    $mdl = str_replace('YK3610 III', 'YK3610III', $mdl);
                                    $mdl = str_replace('YK3610 II', 'YK3610II', $mdl);
                                    $mdl = str_replace('S480x1000)', 'S480x1000', $mdl);
                                    $mdl = str_replace('H-260 HB', 'H-260HB', $mdl);
                                    $mdl = str_replace('DC M-', 'DCM-', $mdl);
                                    $mdl = str_replace(' / ', '/', $mdl);
                                    $mdl = str_replace(' /Z', '/Z', $mdl);
                                    $mdl = str_replace(' II', 'II', $mdl);
                                    $mdl = str_replace(' ATC', 'ATC', $mdl);
                                    $mdl = str_replace(' АТС', 'ATC', $mdl);
                                    $mdl = str_replace('Р-150 В', 'Р-150В', $mdl);
                                    $mdl = str_replace('АHPC ', 'АHPC', $mdl);
                                    $mdl = str_replace('А-6 B 102', 'А-6B102', $mdl);
                                    $mdl = str_replace(' Bx3', 'Bx3', $mdl);
                                    $mdl = str_replace('M 80', 'M80', $mdl);
                                    $mdl = str_replace('80 T', '80T', $mdl);
                                    $mdl = str_replace('HA NC', 'HANC', $mdl);
                                    $mdl = str_replace('H B NC', 'HB(NC)', $mdl);
                                    $mdl = str_replace('H B(NC)', 'HB(NC)', $mdl);
                                    $mdl = str_replace('H-460 ', 'H-460', $mdl);
                                    $mdl = str_replace(' HANC', 'HANC', $mdl);
                                    $mdl = str_replace('HANC', 'HA(NC)', $mdl);
                                    $mdl = str_replace('HBNC', 'HA(NC)', $mdl);
                                    $mdl = str_replace('GU-32120 CNC', 'GU-32120CNC', $mdl);
                                    $mdl = str_replace(' L2Y', 'L2Y', $mdl);
                                    $mdl = str_replace('GS-200 M S/N 91B1387', 'GS-200M', $mdl);
                                    $mdl = str_replace('H-260HB NC EVERISING', 'H-260HB(NC)', $mdl);
                                    $mdl = str_replace('LK MACHINERY LH-500 LH-500', 'LK MACHINERY LH-500', $mdl);
                                    $mdl = str_replace('(SIEMENS)', '', $mdl);
                                    $mdl = str_replace(' TF-III', 'TF-III', $mdl);
                                    $mdl = str_replace('ПОРТАЛЬНОГО ТИПА', '', $mdl);
                                    $mdl = str_replace('x ', 'x', $mdl);
                                    $mdl = str_replace(' L 2', 'L2', $mdl);
                                    $mdl = str_replace('SEM A T', 'SEMAT', $mdl);
                                    $mdl = str_replace('18 S-NC', '18S-NC', $mdl);
                                    $mdl = str_replace(' × ', '×', $mdl);
                                    $mdl = str_replace('// ', '//', $mdl);
                                    $mdl = str_replace('JL-V ', 'JL-V', $mdl);
                                    $mdl = str_replace('af-', 'AF-', $mdl);
                                    $mdl = str_replace('f3T3', 'f3 (T3)', $mdl);
                                    $mdl = str_replace('PHOENIX FL-30156 .0кВт', 'PHOENIX FL-30156', $mdl);
                                    $mdl = str_replace('P ALLET', 'PALLET', $mdl);
                                    $mdl = str_replace('в составе', '', $mdl);
                                    $mdl = str_replace(' А МС', 'АМС', $mdl);
                                    $mdl = str_replace('АМС', 'AMC', $mdl);
                                    $mdl = str_replace('T G', 'TG', $mdl);
                                    $mdl = str_replace('T N L', 'TNL', $mdl);
                                    $mdl = str_replace('двухколонного т и па', '', $mdl);
                                    $mdl = str_replace('TAKISAWA', '', $mdl);
                                    $mdl = str_replace('S 012-5', 'S012-5', $mdl);
                                    $mdl = str_replace('S 020-5', 'S020-5', $mdl);
                                    $mdl = str_replace('Oturn', 'OTURN', $mdl);
                                    $mdl = str_replace('Quick-Tech', 'QUICK-TECH', $mdl);
                                    $mdl = str_replace('A CCURATE', 'ACCURATE', $mdl);
                                    $mdl = str_replace('M ULTI', 'MULTI', $mdl);
                                    $mdl = str_replace('Seiki', 'SEIKI', $mdl);
                                    $mdl = str_replace('U LTRA', 'ULTRA', $mdl);
                                    $mdl = str_replace('Tornos', 'TORNOS', $mdl);
                                    */

                                    $mdl = str_replace('  ', ' ', $mdl);
                                    $mdl = str_replace('  ', ' ', $mdl);
                                    
                                    $mdl = trim($mdl, '.');
                                    $mdl = trim($mdl, ':');
                                    
                                    $mdl = trim($mdl);

                                    if (mb_strlen($mdl) > 1)
                                    {
                                        $model[$z] = $mdl;
                                        $modelArr[mb_strtolower($mdl)] = $mdl;
                                    }
                                    else
                                    {
                                        unset($model[$z]);
                                    }
                                }
                            }
                        }

                        if ($header <> '' && !mb_strstr($header, 'ТКП №') && !mb_strstr($header, 'ТКП No')) //$isStartWord && !$exclude && $headerUpper <> 'ТЕХНИКО-КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ' && $headerUpper <> 'КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ' && $headerUpper <> 'ТЕХНИЧЕСКОЕ ПРЕДЛОЖЕНИЕ')
                        {
                            $newHeaders[] = $header;
                        }

                        $header = trim($header);

                        $headerLower = mb_strtolower($header);

                        if (preg_match('/^[\p{Cyrillic}\s]+$/u', $header) || mb_strstr($headerLower, 'контракт') || mb_strstr($headerLower, 'двухрядные') || mb_strlen($header) > 150)
                        {
                            $header = '';
                        }

                        if ($header <> '' && !$exclude)
                        {
                            $headersArr[mb_strtoupper($header)] = $header;
                        }
                    }

                    if (!$exclude && count($newHeaders) > 0)
                    {
                        //dump($tables);exit;

                        $tsTable = $tables['technical_specifications'] ?? [];

                        $isExistTsTableData = false;

                        if (1 > 0 || isset($tsTable[0][0][0]) && count($tsTable[0]) == 1 && count($tsTable[0][0]) == 1)
                        {
                            //print_r('<pre> File:  '.$fileName.'</pre>');
                            $tableTitle = $tsTable[0][0][0];

                            $tableTitle = trim(str_replace('  ', ' ', $tableTitle));

                            $tableTitle = mb_strtoupper($tableTitle);

                            if (
                                1>0
                                ||
                                mb_strstr($tableTitle, 'ОСНОВНЫЕ Т ЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ')
                                ||
                                mb_strstr($tableTitle, 'ОСНОВНЫЕ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ')
                                ||
                                mb_strstr($tableTitle, 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ СТАНКА')
                                ||
                                mb_strstr($tableTitle, 'Т ЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ СТАНКА')
                                ||
                                mb_strstr($tableTitle, 'О СНОВНЫЕ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ')
                               )
                            {
                                if (1>0 || !in_array($tableTitle, $tsTableTtl))
                                {
                                    $tsTableTtl[] = $tableTitle;

                                    // Ищем таблицу с техническими характеристиками
                                    $techTableNumber = -1;
                                    foreach($tsTable as $z => $rows)
                                    {
                                        if (count($rows) >= 5)
                                        {
                                            foreach($rows as $cols)
                                            {
                                                if (in_array(count($cols), [2,3]))
                                                {
                                                    $nameUpper  = mb_strtoupper($cols[0]);
                                                    $isTechName = (mb_strstr($nameUpper, 'ШПИНДЕЛ') || mb_strstr($nameUpper, 'ПО ОСИ') || mb_strstr($nameUpper, 'КРУТЯЩИЙ МОМЕНТ')) ? true : false;

                                                    if ($isTechName)
                                                    {
                                                        $techTableNumber = $z;
                                                        break 2;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $tsTableData = [];

                                    if (!$isTechName)
                                    {
                                        if (isset($tsTable[0][1]) && count($tsTable[0][0]) == 3 && count($tsTable[0][1]) == 4 && mb_strtoupper($tsTable[0][0][0]) == 'ПАРАМЕТР' && mb_strtoupper($tsTable[0][1][0]) == $tsTable[0][1][0] && mb_strtoupper($tsTable[0][0][1]) == 'ЕД. ИЗМ.' && mb_strtoupper($tsTable[0][0][2]) == 'ЗНАЧЕНИЕ')
                                        {
                                            $tsTable[0][0] =
                                            [
                                                0 => "Группа",
                                                1 => "Параметр",
                                                2 => "Ед. изм.",
                                                3 => "Значение",
                                            ];

                                            if (isset($tsTable[1])) unset($tsTable[1]);

                                            foreach($tsTable as $z => $rows)
                                            {
                                                if (count($rows) >= 5)
                                                {
                                                    foreach($rows as $cols)
                                                    {
                                                        $nameUpper  = mb_strtoupper($cols[1]);
                                                        $isTechName = (mb_strstr($nameUpper, 'ШПИНДЕЛ') || mb_strstr($nameUpper, 'ПО ОСИ')) ? true : false;

                                                        if ($isTechName)
                                                        {
                                                            $techTableNumber = $z;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }

                                            if ($isTechName)
                                            {
                                                $prevGroup = '';
                                                $prevParam = '';
                                                $prevUnit  = '';
                                                $prevValue = '';

                                                foreach($tsTable[0] as $z => $rows)
                                                {
                                                    if ($z > 0)
                                                    {
                                                        $curGroup = trim($rows[0]);
                                                        $curParam = trim($rows[1]);

                                                        if ($curGroup == '') $curGroup = $prevGroup;
                                                        if ($curParam == '') $curParam = $prevParam;

                                                        if (count($rows) == 5)
                                                        {
                                                            $tsTable[0][$z][1] = $curParam.' '.$rows[2];
                                                            $tsTable[0][$z][2] = $tsTable[0][$z][3];
                                                            $tsTable[0][$z][3] = $tsTable[0][$z][4];
                                                            unset($tsTable[0][$z][4]);
                                                        }

                                                        if ($curParam == 'ЧПУ')
                                                        {
                                                            $tsTable[0][$z][2] = '';
                                                        }

                                                        if ($tsTable[0][$z][2] == '--')
                                                        {
                                                            $tsTable[0][$z][2] = '';
                                                        }

                                                        $curUnit  = trim($tsTable[0][$z][2]);
                                                        
                                                        if ($curUnit  == '') $curUnit  = $prevUnit;

                                                        $curGroup = str_replace('ОБЩАЯИНФОРМАЦИЯ', 'ОБЩАЯ ИНФОРМАЦИЯ', $curGroup);
                                                        
                                                        if (
                                                            $rows[1] == 'ЧПУ'
                                                            ||
                                                            mb_strlen($curGroup) <= 1
                                                            ||
                                                            is_numeric($curGroup)
                                                            ||
                                                            mb_strstr($curGroup, 'ОСНОВНЫЕ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ')
                                                            ||
                                                            mb_strstr($curGroup, 'ОСНОВНЫЕ ТЕХНОЛОГИЧЕСКИЕ ВОЗМОЖНОСТИ')
                                                            ||
                                                            mb_strstr($curGroup, 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ| ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИПАРАМЕТР')
                                                            ||
                                                            mb_strstr($curGroup, 'МОДЕЛЬ')
                                                        )
                                                        {
                                                            $curGroup = 'NONE';
                                                        }

                                                        $tsTable[0][$z][0] = $curGroup;

                                                        if (count($rows) <> 5)
                                                        {
                                                            $tsTable[0][$z][1] = $curParam;
                                                            
                                                        }

                                                        $tsTable[0][$z][2] = $curUnit;
                                                        
                                                        $prevGroup = $curGroup;
                                                        $prevParam = $curParam;

                                                        if (!isset($tsTableData[$curGroup][(string)trim($curParam)])) $tsTableData[$curGroup][(string)trim($curParam)] = [];

                                                        $tsTableData[$curGroup][(string)trim($curParam)][] =
                                                        [
                                                            'unit'      => $curUnit,
                                                            'value'     => $tsTable[0][$z][3],
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (count($tsTableData) == 0)
                                    {
                                        if (isset($tsTable[$techTableNumber][1]) && count($tsTable[$techTableNumber][0]) == 2 && count($tsTable[$techTableNumber][1]) == 3 /*&& mb_strtoupper($tsTable[0][0][0]) == 'ПАРАМЕТР' && mb_strtoupper($tsTable[0][1][0]) == $tsTable[0][1][0] && mb_strtoupper($tsTable[0][0][1]) == 'ЕД. ИЗМ.' && mb_strtoupper($tsTable[0][0][2]) == 'ЗНАЧЕНИЕ'*/)
                                        {
                                            //print_r('<pre> File:  '.$fileName.'</pre>');
                                            //print_r('<pre> File:  '.$jsonFileName.'</pre>');

                                            $newTsTable = $tsTable[$techTableNumber];
                                            foreach($tsTable as $curTechTableNumber => $tableRows)
                                            {
                                                if ($curTechTableNumber > $techTableNumber && isset($tableRows[0][0]) && mb_strstr(mb_strtoupper($tableRows[0][0]), 'БАБКА'))
                                                {
                                                    foreach($tableRows as $row)
                                                    {
                                                        $newTsTable[] = $row;
                                                    }
                                                    //dump($tableRows);
                                                }
                                            }

                                            foreach($newTsTable as $z => $rows)
                                            {
                                                if ($z == 0)
                                                {
                                                    $newTsTable[$z][2] = $newTsTable[$z][1];
                                                    $newTsTable[$z][1] = '';
                                                    break;
                                                }
                                            }

                                            $groupName     = 'NONE';
                                            $prevGroupName = 'NONE';

                                            foreach($newTsTable as $rows)
                                            {
                                                if (count($rows) == 1 && mb_strtoupper(trim($rows[0])) == trim($rows[0]))
                                                {
                                                    $groupName = trim($rows[0]);
                                                }
                                                else
                                                {
                                                    $groupName = $prevGroupName;
                                                }

                                                $prevGroupName = $groupName;

                                                //dump($groupName);

                                                if (count($rows) == 3)
                                                {
                                                    if (!isset($tsTableData[$groupName][(string)trim($rows[0])])) $tsTableData[$groupName][(string)trim($rows[0])] = [];

                                                    $tsTableData[$groupName][(string)trim($rows[0])][] =
                                                    [
                                                        'unit'  => (string)trim($rows[1]),
                                                        'value' => (string)trim($rows[2]),
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                    
                                    $groupName = 'NONE';

                                    //if ($fileName == '201210_ТКП_Сварочный комплекс Yaskawa_Гефест.docx')
                                    //{
                                    //    dump(count($tsTableData), $techTableNumber, $tsTable);
                                    //    exit;
                                    //}

                                    if (count($tsTableData) == 0 && $techTableNumber >= 0 && count($tsTable[$techTableNumber]) >= 5)
                                    {
                                        //if ($fileName == '201210_ТКП_Сварочный комплекс Yaskawa_Гефест.docx') dd($jsonFileName);
                                        foreach($tsTable[$techTableNumber] as $z => $cols)
                                        {
                                            if ((count($cols) == 1 || (count($cols) == 2 && trim($cols[1]) == '')) && isset($tsTable[$techTableNumber][$z+1]) && count($tsTable[$techTableNumber][$z+1]) > 1)
                                            {
                                                $groupName = trim(mb_strtoupper($cols[0]));
                                                if (mb_strstr($groupName, 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ'))
                                                {
                                                    $groupName = 'NONE';
                                                }
                                            }
                                            elseif (in_array(count($cols), [2,3]))
                                            {
                                                $nameUpper = mb_strtoupper($cols[0]);

                                                if ($cols[0] <> '' && $cols[0] === $nameUpper)
                                                {
                                                    $groupName = trim($cols[0]);

                                                    $groupNameUpper = mb_strtoupper($groupName);

                                                    $groupNameUpper = str_replace('ТОЧНОСТЬ (VDI ISO230-2 )', 'ТОЧНОСТЬ (VDI ISO230-2)', $groupNameUpper);

                                                    $el = explode('-', $groupNameUpper);

                                                    if (
                                                        mb_strlen($groupNameUpper) <= 1
                                                        ||
                                                        (count($el) == 2 && is_numeric($el[0]) && is_numeric($el[1]))
                                                        ||
                                                        is_numeric($groupNameUpper)
                                                        ||
                                                        mb_strstr($groupNameUpper, 'ОСНОВНЫЕ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ')
                                                        ||
                                                        mb_strstr($groupNameUpper, 'ОСНОВНЫЕ ТЕХНОЛОГИЧЕСКИЕ ВОЗМОЖНОСТИ')
                                                        ||
                                                        mb_strstr($groupNameUpper, 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ| ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИПАРАМЕТР')
                                                        ||
                                                        mb_strstr($groupNameUpper, 'МОДЕЛЬ')
                                                    )
                                                    {
                                                        $groupName      = 'NONE';
                                                        $groupNameUpper = 'NONE';
                                                    }

                                                    $groupName = $groupNameUpper;

                                                    if (!in_array($groupNameUpper, $groupsArr))
                                                    {
                                                        $groupsArr[] = $groupNameUpper;
                                                    }
                                                }
                                                else
                                                {
                                                    $paramName      = trim($cols[0]);
                                                    $paramNameUpper = trim(mb_strtoupper($paramName));

                                                    if ($paramNameUpper <> 'МОДЕЛЬ')
                                                    {
                                                        if (count($cols) == 2)
                                                        {
                                                            $unit  = '';
                                                            $value = (string)trim($cols[1]);
                                                        }
                                                        elseif (count($cols) == 3)
                                                        {
                                                            $unit  = (string)trim($cols[1]);
                                                            $value = (string)trim($cols[2]);
                                                        }

                                                        if (!isset($tsTableData[$groupName][(string)trim($paramName)])) $tsTableData[$groupName][(string)trim($paramName)] = [];
                                                        
                                                        $tsTableData[$groupName][(string)trim($paramName)][] =
                                                        [
                                                            'unit'  => $unit,
                                                            'value' => $value,
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }



                                    if(count($tsTableData) == 0)
                                    {
                                        $tableCount = 0;

                                        $curFirstTable = [];

                                        foreach($tsTable as $curTable)
                                        {
                                            $isUnitTitle = (isset($curTable[0][1]) && mb_strstr(trim(mb_strtoupper($curTable[0][1])), 'ЕД. ИЗМ.')) ? true : false;

                                            if ($isUnitTitle || (count($curTable) >= 8 && count($curTable) <= 32 && count($curTable[0]) >= 2))
                                            {
                                                $isFirstColNum = (trim($curTable[0][0]) == "№") ? true : false;
                                                
                                                if (!$isFirstColNum && (count($curTable[0]) >= 3 || (count($curTable[0]) == 2 && isset($curTable[1]) && count($curTable[1]))))
                                                {
                                                    $param = trim(mb_strtoupper($curTable[0][1]));
                                                    $excludeCurrent = ($param == 'НАИМЕНОВАНИЕ ОПЦИЙ' || (count($curTable[0]) == 2 && is_numeric($curTable[0][0]))) ? true : false;

                                                    /*
                                                    if (
                                                        trim(mb_strtoupper($curTable[0][0])) == 'МОДЕЛЬ'
                                                        ||
                                                        mb_strstr(trim(mb_strtoupper($curTable[0][0])), 'МОДЕЛЬ:')
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'МОДЕЛЬ СТАНКА'
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'ХАРАКТЕРИСТИКИ'
                                                        ||
                                                        (trim(mb_strtoupper($curTable[0][0])) == 'ПАРАМЕТР' && trim(mb_strtoupper($curTable[0][1]) == 'ЗНАЧЕНИЕ'))
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'СЕРИЯ'
                                                        ||
                                                        (trim(mb_strtoupper($curTable[0][0])) == 'ПАРАМЕТР' && trim(mb_strtoupper($curTable[0][1]) == 'ЕД. ИЗМ.'))
                                                        ||
                                                        (trim(mb_strtoupper($curTable[0][1])) == 'СПЕЦИФИКАЦИЯ' && isset($curTable[0][3]) && trim(mb_strtoupper($curTable[0][3]) == 'FERZ'))
                                                        ||
                                                        (isset($curTable[0][3]) && trim(mb_strtoupper($curTable[0][3]) == 'CYW50-2000'))
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0]) == 'Тип станка')
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'МАКСИМАЛЬНЫЕ РАЗМЕРЫ ЗАГОТОВКИ, ММ'
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'МАКСИМАЛЬНЫЕ РАЗМЕРЫ РАСПИЛА, ММ'
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'МЕХАНИЧЕСКИЕ ХАРАКТЕРИСТИКИ'
                                                        ||
                                                        (mb_strstr(trim(mb_strtoupper($curTable[0][0])), 'ОСНОВНЫЕ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ') && isset($curTable[1][1]) && trim(mb_strtoupper($curTable[1][1])) == 'ЕД. ИЗМ.')
                                                        ||
                                                        (trim(mb_strtoupper($curTable[0][0])) == 'УЗЕЛ' && isset($curTable[0][1]) && trim(mb_strtoupper($curTable[0][1]) == 'ЕДИНИЦА ИЗМЕРЕНИЯ'))
                                                        ||
                                                        (trim(mb_strtoupper($curTable[0][0])) == 'НАИМЕНОВАНИЕ' && isset($curTable[0][1]) && mb_strstr(mb_strtoupper($curTable[0][1]), 'PC-'))
                                                        ||
                                                        trim(mb_strtoupper($curTable[0][0])) == 'ДЛИНА ГИБКИ, ММ'
                                                        ||
                                                        (trim(mb_strtoupper($curTable[0][0])) == 'ТЕХНИЧЕСКИЕ ПАРАМЕТРЫ' && $isUnitTitle)
                                                        )
                                                    {
                                                        $tableCount++;
                                                        $curFirstTable = $curTable;
                                                    }
                                                    */

                                                    $groupColumn = -1;
                                                    $paramColumn = -1;
                                                    $unitColumn  = -1;
                                                    $valueColumn = -1;

                                                    $tabColCountUniq = [];
                                                    foreach($curTable as $cols)
                                                    {
                                                        $countCols = count($cols);
                                                        if (!in_array($countCols, $tabColCountUniq))
                                                        {
                                                            $tabColCountUniq[] = $countCols;
                                                        }
                                                    }

                                                    if (!$excludeCurrent)
                                                    {
                                                        $defaultGroupName = 'NONE';
                                                        $rowStart         = -1;

                                                        if (trim(mb_strtoupper($curTable[0][0])) == 'МОДЕЛЬ' || mb_strstr(trim(mb_strtoupper($curTable[0][0])), 'МОДЕЛЬ:'))
                                                        {
                                                            if (count($curTable[0]) == 2)
                                                            {
                                                                $unitColumn = -1;
                                                                if (count($curTable[1]) == 2)
                                                                {
                                                                    $rowStart    = 1;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                            }
                                                            elseif(count($curTable[0]) == 3 && trim($curTable[0][1]) == '' && trim($curTable[0][2]) <> '')
                                                            {
                                                                if (count($curTable[1]) == 2 && trim($curTable[1][0]) <> '' && trim($curTable[1][1]) <> '' && count($curTable[2]) == 3)
                                                                {
                                                                    $rowStart = 1;
                                                                    $curTable[1][2] = $curTable[1][1];
                                                                    $curTable[1][1] = '';
                                                                    $groupColumn = 0;
                                                                    $paramColumn = 1;
                                                                    $valueColumn = 2;
                                                                }
                                                                else
                                                                {
                                                                    $rowStart    = 1;
                                                                    $groupColumn = 0;
                                                                    $paramColumn = 1;
                                                                    $valueColumn = 2;
                                                                }
                                                            }
                                                        }
                                                        elseif(count($curTable[0]) == 2 && count($curTable[1]) == 3)
                                                        {
                                                            $rowStart    = 1;
                                                            $groupColumn = 0;
                                                            $paramColumn = 1;
                                                            $valueColumn = 2;
                                                        }
                                                        elseif(count($curTable[0]) == 2 && count($curTable[1]) == 2)
                                                        {
                                                            if (trim(mb_strtoupper($curTable[0][0])) == 'ПАРАМЕТР' && trim(mb_strtoupper($curTable[0][1])) == 'ЗНАЧЕНИЕ')
                                                            {
                                                                $rowStart    = 2;
                                                                $paramColumn = 0;
                                                                $valueColumn = 1;
                                                            }
                                                            elseif(count($tabColCountUniq) == 1 && $tabColCountUniq[0] == 2)
                                                            {
                                                                if (trim(mb_strtoupper($curTable[0][0])) == 'КАРТИНКА' || trim(mb_strtoupper($curTable[1][0])) == trim(mb_strtoupper($curTable[1][1])))
                                                                {}
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'МЕХАНИЧЕСКИЕ ХАРАКТЕРИСТИКИ' && trim(mb_strtoupper($curTable[0][1])) == 'МЕХАНИЧЕСКИЕ ХАРАКТЕРИСТИКИ' && (trim(mb_strtoupper($curTable[1][0])) == 'МОДЕЛЬ' || trim(mb_strtoupper($curTable[1][0])) == 'ПЕРЕМЕЩЕНИЕ ПО ОСЯМ ХYZ, ММ'))
                                                                {
                                                                    $rowStart    = 2;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ' && trim(mb_strtoupper($curTable[0][1])) == 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ' && trim(mb_strtoupper($curTable[1][0])) == 'МОДЕЛЬ')
                                                                {
                                                                    $rowStart    = 2;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'ТИП РАСХОДНОГО МАТЕРИАЛА' && trim(mb_strtoupper($curTable[0][1])) == 'СРОК СЛУЖБЫ/ ТОЛЩИНЫ')
                                                                {}
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'ПОДАЧА ЗАГОТОВОК')
                                                                {
                                                                    $rowStart    = 0;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'ХАРАКТЕРИСТИКА' && trim(mb_strtoupper($curTable[0][1])) == 'ЗНАЧЕНИЕ')
                                                                {
                                                                    $rowStart    = 1;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'НАИМЕНОВАНИЕ ПАРАМЕТРА' && trim(mb_strtoupper($curTable[0][1])) == 'ЗНАЧЕНИЕ')
                                                                {
                                                                    $rowStart    = 1;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'МОДЕЛЬ РОБОТА' && trim(mb_strtoupper($curTable[0][1])) <> '')
                                                                {
                                                                    $rowStart    = 1;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                                elseif(trim(mb_strtoupper($curTable[0][0])) == 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ MT1-1500 2SD' && trim(mb_strtoupper($curTable[0][1])) == 'ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ MT1-1500 2SD')
                                                                {
                                                                    $rowStart    = 1;
                                                                    $paramColumn = 0;
                                                                    $valueColumn = 1;
                                                                }
                                                            }
                                                        }
                                                        else
                                                        {
                                                            //print_r('<pre> File:  '.$fileName.'</pre>');
                                                            //dump($curTable);
                                                        }

                                                        if ($rowStart >= 0)
                                                        {
                                                            $tableCount++;
                                                            $curFirstTable = $curTable;

                                                            $prevGroup = 'NONE';
                                                            $prevParam = '';
                                                            $prevUnit  = '';
                                                            $prevValue = '';

                                                            $curGroup = 'NONE';
                                                            $curParam = '';
                                                            $curUnit  = '';
                                                            $curValue = '';

                                                            // костыль
                                                            foreach($curFirstTable as $z => $rows)
                                                            {
                                                                if (count($curFirstTable[$z]) == 3 && trim(mb_strtoupper($curFirstTable[$z][0])) == 'МАКСИМАЛЬНЫЕ РАЗМЕРЫ ЗАГОТОВКИ, ММ')
                                                                {
                                                                    $el = explode('x', $curFirstTable[$z][2]);
                                                                    if (count($el) <> 2) $el = explode('х', $curFirstTable[$z][2]);

                                                                    $curFirstTable[$z][1] = count($el) == 1 ? '●' : '■';

                                                                    $el = explode('x', $curFirstTable[$z+1][2]);
                                                                    if (count($el) <> 2) $el = explode('х', $curFirstTable[$z+1][2]);

                                                                    $curFirstTable[$z+1] =
                                                                    [
                                                                        0 => $curFirstTable[$z+1][0],
                                                                        1 => count($el) == 1 ? '●' : '■',
                                                                        2 => $curFirstTable[$z+1][2],
                                                                    ];

                                                                    break;
                                                                }
                                                            }

                                                            foreach($curFirstTable as $z => $rows)
                                                            {
                                                                if ($z >= $rowStart)
                                                                {
                                                                    $curGroupColumn = $groupColumn;
                                                                    $curParamColumn = $paramColumn;
                                                                    $curUnitColumn  = $unitColumn;
                                                                    $curValueColumn = $valueColumn;

                                                                    if ($groupColumn >= 0 && trim($rows[$paramColumn]) == '')
                                                                    {
                                                                        $curGroupColumn = -1;
                                                                        $curParamColumn = 0;
                                                                    }

                                                                    $curGroup = ($curGroupColumn >= 0) ? trim($rows[$curGroupColumn]) : 'NONE';
                                                                    $curParam = trim($rows[$curParamColumn]);
                                                                    $curValue = (isset($rows[$curValueColumn])) ? trim($rows[$curValueColumn]) : trim($rows[$curValueColumn-1]);
                                                                    if ($curUnitColumn >= 0) $curUnit = trim($rows[$curUnitColumn]);

                                                                    if ($prevGroup <> $curGroup && $curGroup <> '')
                                                                    {
                                                                        $prevParam = '';
                                                                        $prevUnit  = '';
                                                                        $prevValue = '';
                                                                    }

                                                                    if ($prevParam <> $curParam && $curParam <> '')
                                                                    {
                                                                        $prevUnit  = '';
                                                                        $prevValue = '';
                                                                    }

                                                                    if ($curGroup == '') $curGroup = $prevGroup;
                                                                    if ($curParam == '') $curParam = $prevParam;
                                                                    if ($curValue == '') $curValue = $prevValue;
                                                                    if ($curUnitColumn >= 0 && $curUnit == '') $curUnit = $prevUnit;

                                                                    $prevGroup = $curGroup;
                                                                    $prevParam = $curParam;
                                                                    $prevValue = $curValue;
                                                                    $prevUnit  = $curUnit;

                                                                    if ($curParam <> '' && $curValue <> '')
                                                                    {
                                                                        if (!isset($tsTableData[$curGroup][(string)$curParam]))
                                                                        {
                                                                            $tsTableData[$curGroup][(string)$curParam] = [];
                                                                        }

                                                                        $tsTableData[$curGroup][(string)$curParam][] =
                                                                        [
                                                                            'unit'  => $curUnit,
                                                                            'value' => $curValue,
                                                                        ];
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    
                                    // КОМПЛЕКТАЦИИ
                                    if(count($tsTableData) > 0)
                                    {
                                        $compTables = ['base' => [], 'dop' => []];

                                        $startRow = -1;
                                        $paramCol = -1;
                                        $unitCol  = -1;
                                        $valueCol = -1;

                                        $isDebug = false;

                                        $compExclude = false;

                                        $subTable = ['base' => [], 'dop' => []];

                                        if (count($tables['pricing']) > 0)
                                        {
                                            foreach($tables as $tableType => $tablesArr)
                                            {
                                                foreach($tablesArr as $table)
                                                {
                                                    $firstFieldNumericCount = 0;
                                                    $colCounter = [];
                                                    foreach($table as $rows)
                                                    {
                                                        if (isset($rows[0]) && trim($rows[0]) <> '' && is_numeric(trim($rows[0]))) $firstFieldNumericCount++;
                                                        $colCount = count($rows);
                                                        if (!in_array($colCount, $colCounter)) { $colCounter[] = $colCount; }
                                                    }

                                                    if (count($colCounter) == 1 && in_array($colCounter[0], [2,3]))
                                                    {
                                                        if (isset($table[0][0]) && isset($table[1][0]) && trim($table[1][0]) == '№' && isset($table[1][1]) && trim($table[1][1]) <> '' && (mb_strtoupper(trim($table[0][0])) == 'БАЗОВАЯ КОМПЛЕКТАЦИЯ' || mb_strtoupper(trim($table[0][0])) == 'ОПЦИОНАЛЬНАЯ КОМПЛЕКТАЦИЯ'))
                                                        {
                                                            $startRow = 2;
                                                            $paramCol = 1;
                                                            if (mb_strtoupper(trim($table[0][0])) == 'БАЗОВАЯ КОМПЛЕКТАЦИЯ')
                                                            {
                                                                if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                            }
                                                            elseif (mb_strtoupper(trim($table[0][0])) == 'ОПЦИОНАЛЬНАЯ КОМПЛЕКТАЦИЯ')
                                                            {
                                                                if (count($subTable['dop']) == 0) $subTable['dop'] = $table;
                                                            }
                                                        }
                                                        elseif(isset($table[0][0]) && isset($table[0][1]) && mb_strtoupper(trim($table[0][0])) == 'СТАНДАРТНАЯ КОМПЛЕКТАЦИЯ' && mb_strtoupper(trim($table[0][1])) == 'ДОПОЛНИТЕЛЬНОЕ КОМПЛЕКТАЦИЯ' )
                                                        {
                                                            // пропускаем 1 комплектацию
                                                            $compExclude = true;
                                                        }
                                                        elseif(isset($table[0][0]) && mb_strtoupper(trim($table[0][0])) == 'КОМПЛЕКТАЦИЯ СТАНКА')
                                                        {
                                                            $startRow = 2;
                                                            $paramCol = 1;
                                                            $valueCol = 2;
                                                            if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                            //dump('fileName: '.$fileName, $table);
                                                        }
                                                        elseif(isset($table[0][1]) && (mb_strtoupper(trim($table[0][1])) == 'КОМПЛЕКТАЦИЯ СТАНКА' || mb_strtoupper(trim($table[0][1])) == 'ДОПОЛНИТЕЛЬНАЯ КОМПЛЕКТАЦИЯ'))
                                                        {
                                                            $startRow = 2;
                                                            $paramCol = 1;
                                                            $valueCol = -1;
                                                            if (mb_strtoupper(trim($table[0][1])) == 'КОМПЛЕКТАЦИЯ СТАНКА')
                                                            {
                                                                if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                            }
                                                            elseif (mb_strtoupper(trim($table[0][1])) == 'ДОПОЛНИТЕЛЬНАЯ КОМПЛЕКТАЦИЯ')
                                                            {
                                                                if (count($subTable['dop']) == 0) $subTable['dop'] = $table;
                                                            }

                                                            //dump('fileName: '.$fileName, $tables);
                                                        }
                                                        elseif(isset($table[0][0]) && isset($table[0][1]) && isset($table[0][2]) && $table[0][0] == '№') // && mb_strtoupper(trim($table[0][1])) == 'НАИМЕНОВАНИЕ ПОЗИЦИЙ' && mb_strtoupper(trim($table[0][1])) == 'КОЛ-ВО')
                                                        {
                                                            if (mb_strstr(trim(mb_strtoupper($table[0][1])), 'НАИМЕНОВАНИЕ') || mb_strstr(trim(mb_strtoupper($table[0][1])), 'НАЗВАНИЕ') || mb_strstr(trim(mb_strtoupper($table[0][1])), 'ИНСТРУМЕНТ / ОСНАСТКА') || mb_strtoupper(trim($table[0][1])) == 'ТОВАР' || mb_strtoupper(trim($table[0][1])) == 'КОМПЛЕКТ ИНСТРУМЕНТАЛЬНОЙ ОСНАСТКИ' || mb_strtoupper(trim($table[0][1])) == 'ОБОЗНАЧЕНИЕ')
                                                            {
                                                                $startRow = 1;
                                                                $paramCol = 1;
                                                                $valueCol = 2;
                                                                if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                                //dump('fileName: '.$fileName, $table);
                                                            }
                                                            else
                                                            {
                                                                $compExclude = true;
                                                            }
                                                        }
                                                    }
                                                    elseif (count($colCounter) == 1 && in_array($colCounter[0], [2]) && mb_strtoupper(trim($table[0][0])) == 'НАИМЕНОВАНИЕ')
                                                    {
                                                        $nTable = [];
                                                        foreach($table as $row)
                                                        {
                                                            if (!mb_strstr(mb_strtoupper(trim($row[0])), 'БАЗОВАЯ КОМПЛЕКТАЦИЯ') && !mb_strstr(mb_strtoupper(trim($row[0])), 'ИТОГО'))
                                                            {
                                                                $nTable[] = $row;
                                                            }
                                                        }
                                                        $table = $nTable;

                                                        if (mb_strtoupper(trim($table[0][1])) == 'ЦЕНА')
                                                        {
                                                            $startRow = 1;
                                                            $paramCol = 0;
                                                            $valueCol = -1;

                                                            $tbKey = 'base';
                                                            
                                                            foreach($table as $z => $row)
                                                            {
                                                                if (mb_strtoupper(trim($row[0])) == 'ДОПОЛНИТЕЛЬНАЯ КОМПЛЕКТАЦИЯ')
                                                                {
                                                                    $tbKey = 'dop';
                                                                }
                                                                else
                                                                {
                                                                    $subTable[$tbKey][] = [$row[0]];
                                                                }
                                                            }
                                                            //dump('fileName: '.$fileName, $table);
                                                        }
                                                        elseif(mb_strstr(mb_strtoupper(trim($table[0][1])), 'КОЛ-ВО'))
                                                        {
                                                            $startRow = 1;
                                                            $paramCol = 0;
                                                            $valueCol = 1;

                                                            $tbKey = 'base';

                                                            foreach($table as $z => $row)
                                                            {
                                                                if (mb_strtoupper(trim($row[0])) == 'ДОПОЛНИТЕЛЬНАЯ КОМПЛЕКТАЦИЯ')
                                                                {
                                                                    $tbKey = 'dop';
                                                                }
                                                                else
                                                                {
                                                                    $subTable[$tbKey][] = [$row[0], $row[1]];
                                                                }
                                                            }
                                                            //dump('fileName: '.$fileName, $table);
                                                        }
                                                    }
                                                    elseif(count($table[0]) == 3 && trim($table[0][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[0][1])), 'НАИМЕНОВАНИЕ') && (mb_strstr(mb_strtoupper(trim($table[0][2])), 'КОЛ-ВО') || mb_strstr(mb_strtoupper(trim($table[0][2])), 'КОЛИЧЕСТВО')))
                                                    {
                                                        $startRow = 1;
                                                        $paramCol = 1;
                                                        $valueCol = 2;

                                                        $nTable = [];
                                                        foreach($table as $row)
                                                        {
                                                            if (!mb_strstr(mb_strtoupper(trim($row[0])), 'ИТОГО') && !mb_strstr(mb_strtoupper(trim($row[0])), 'СТОИМОСТЬ'))
                                                            {
                                                                $nTable[] = $row;
                                                            }
                                                        }
                                                        $table = $nTable;

                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;

                                                        //dump('fileName: '.$fileName, $table);
                                                        //$compExclude = true;
                                                    }
                                                    elseif(count($table[0]) == 3 && trim($table[0][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[0][1])), 'НАИМЕНОВАНИЕ') && mb_strtoupper(trim($table[0][2])) == 'ЦЕНА, USD')
                                                    {
                                                        $compExclude = true;
                                                        //dump('fileName: '.$fileName, $table);
                                                    }
                                                    elseif(count($table) <= 1)
                                                    {
                                                        $compExclude = true;
                                                    }
                                                    elseif(in_array(mb_strtoupper(trim($table[0][0])), ['БАЗОВАЯ КОМПЛЕКТАЦИЯ', 'ОПЦИОНАЛЬНАЯ КОМПЛЕКТАЦИЯ']) && isset($table[1][0]) && isset($table[1][1]) && trim($table[1][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[1][1])), 'НАИМЕНОВАНИЕ'))
                                                    {
                                                        $startRow = 2;
                                                        $paramCol = 1;
                                                        $valueCol = 2;
                                                        if (count($subTable['base']) == 0 && mb_strtoupper(trim($table[0][0])) == 'БАЗОВАЯ КОМПЛЕКТАЦИЯ')      $subTable['base'] = $table;
                                                        if (count($subTable['dop'])  == 0 && mb_strtoupper(trim($table[0][0])) == 'ОПЦИОНАЛЬНАЯ КОМПЛЕКТАЦИЯ') $subTable['dop']  = $table;
                                                    }
                                                    elseif(isset($table[0][1]) && in_array(mb_strtoupper(trim($table[0][1])), ['КОМПЛЕКТАЦИЯ СТАНКА']) && isset($table[1][1]) && trim($table[1][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[1][1])), 'НАИМЕНОВАНИЕ'))
                                                    {
                                                        $startRow = 2;
                                                        $paramCol = 1;
                                                        $valueCol = 2;
                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                    }
                                                    elseif(in_array(mb_strtoupper(trim($table[0][0])), ['ЦЕНА']) && isset($table[1][0]) && isset($table[1][1]) && trim($table[1][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[1][1])), 'НАИМЕНОВАНИЕ'))
                                                    {
                                                        $startRow = 2;
                                                        $paramCol = 1;
                                                        $valueCol = 2;
                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                    }
                                                    elseif(isset($table[0][0]) && in_array(mb_strtoupper(trim($table[0][0])), ['МОДЕЛЬ', 'ОСНОВНЫЕ ПАРАМЕТРЫ', 'ОСНОВНЫЕ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ', 'ПАРАМЕТРЫ ЗОНЫ ОБРАБОТКИ']))
                                                    {
                                                        $compExclude = true;
                                                    }
                                                    elseif(isset($table[0][0]) && isset($table[0][1]) && isset($table[0][2]) && trim($table[0][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[0][1])), 'НАИМЕНОВАНИЕ') && mb_strstr(mb_strtoupper(trim($table[0][2])), 'КОЛ-ВО'))
                                                    {
                                                        $startRow = 1;
                                                        $paramCol = 1;
                                                        $valueCol = 2;

                                                        $nTable = [];
                                                        foreach($table as $row)
                                                        {
                                                            if (isset($row[1]) && !mb_strstr(mb_strtoupper(trim($row[1])), 'ИТОГО') && !mb_strstr(mb_strtoupper(trim($row[1])), 'СТОИМОСТЬ'))
                                                            {
                                                                $nTable[] = $row;
                                                            }
                                                        }
                                                        $table = $nTable;
                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                        //dump('fileName: '.$fileName, $table);
                                                    }
                                                    elseif(isset($table[0][0]) && trim(mb_strtoupper($table[0][0])) == 'КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ')
                                                    {
                                                        $startRow = 2;
                                                        $paramCol = 1;
                                                        $valueCol = 2;

                                                        $nTable = [];
                                                        foreach($table as $row)
                                                        {
                                                            if (isset($row[1]) && !mb_strstr(mb_strtoupper(trim($row[1])), 'ИТОГО') && !mb_strstr(mb_strtoupper(trim($row[1])), 'СТОИМОСТЬ'))
                                                            {
                                                                $nTable[] = $row;
                                                            }
                                                        }
                                                        $table = $nTable;
                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                        
                                                        //dump('fileName: '.$fileName, $table);
                                                    }
                                                    elseif(isset($table[0][0]) && trim(mb_strtoupper($table[0][0])) == 'КОМПЛЕКТАЦИЯ СТАНКА' && isset($table[1][0]) && trim($table[1][0]) == '№')
                                                    {
                                                        $startRow = 2;
                                                        $paramCol = 1;
                                                        $valueCol = 2;
                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                        //dump('fileName: '.$fileName, $table);
                                                    }
                                                    elseif(mb_strstr(mb_strtoupper($fileName), 'РУКОВОДСТВО_ПОЛЬЗОВАТЕЛЯ') || mb_strstr(mb_strtoupper($fileName), 'TEMPLATE_STRUCTURED'))
                                                    {
                                                        $compExclude = true;
                                                        //dump('fileName: '.$fileName, $table);
                                                    }
                                                    elseif(count($table) >= 5 && $firstFieldNumericCount >= 3 && count($colCounter) == 1 && in_array($colCounter[0], [5]) && trim($table[0][0]) == '№' && trim($table[0][1]) == '№' && mb_strtoupper(trim($table[0][2])) == 'НАИМЕНОВАНИЕ ПОЗИЦИЙ' && mb_strtoupper(trim($table[0][3])) == 'НАИМЕНОВАНИЕ ПОЗИЦИЙ' && mb_strtoupper(trim($table[0][4])) == 'КОЛ-ВО')
                                                    {
                                                        $startRow = 1;
                                                        $paramCol = 1;
                                                        $valueCol = 2;

                                                        $nTable = [];
                                                        foreach($table as $row)
                                                        {
                                                            if (isset($row[1]) && !mb_strstr(mb_strtoupper(trim($row[1])), 'ИТОГО') && !mb_strstr(mb_strtoupper(trim($row[1])), 'СТОИМОСТЬ'))
                                                            {
                                                                $nTable[] = $row;
                                                            }
                                                        }
                                                        $table = $nTable;
                                                        if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                        //dump('fileName: '.$fileName, $table);
                                                    }
                                                    elseif(count($table) >= 5 && $firstFieldNumericCount >= 3 && count($colCounter) == 1 && in_array($colCounter[0], [6]) && isset($table[0][0]) && isset($table[1][0]) && (trim($table[0][0]) == '№' || trim($table[1][0]) == '№'))
                                                    {
                                                        if ($table[0][0] == '№')
                                                        {
                                                            if (isset($table[0][1]) && (mb_strstr(mb_strtoupper(trim($table[0][1])), 'НАИМЕНОВАНИЕ') || mb_strstr(mb_strtoupper(trim($table[0][1])), 'ОБОЗНАЧЕНИЕ')))
                                                            {
                                                                if (isset($table[0][3]) && mb_strstr(mb_strtoupper(trim($table[0][3])), 'КОЛ-ВО'))
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 1;
                                                                    $valueCol = 2;
                                                                    if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                                }
                                                                elseif (isset($table[0][4]) && mb_strstr(mb_strtoupper(trim($table[0][4])), 'КОЛ-ВО'))
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 1;
                                                                    $valueCol = 4;
                                                                    $subTable['base'] = $table;
                                                                }
                                                            }
                                                            elseif(mb_strtoupper(trim($table[0][2])) == 'НАИМЕНОВАНИЕ')
                                                            {
                                                                $startRow = 1;
                                                                $paramCol = 2;
                                                                $valueCol = 3;
                                                                if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                            }
                                                            elseif(mb_strtoupper(trim($table[0][1])) == 'ОПИСАНИЕ')
                                                            {
                                                                $startRow = 1;
                                                                $paramCol = 1;
                                                                $valueCol = 2;
                                                                $subTable['base'] = $table;
                                                            }
                                                        }
                                                        elseif(mb_strtoupper(trim($table[0][0])) == 'СТОИМОСТЬ ОБОРУДОВАНИЯ')
                                                        {
                                                            $startRow = 2;
                                                            $paramCol = 1;
                                                            $valueCol = 4;
                                                            $subTable['base'] = $table;
                                                        }
                                                        elseif($fileName <> '20190310_ТКП Awea NV850.docx')
                                                        {
                                                            $startRow = 2;
                                                            $paramCol = 2;
                                                            $valueCol = 4;
                                                            if (count($subTable['base']) == 0) $subTable['base'] = $table;
                                                        }
                                                        else
                                                        {
                                                            $startRow = 2;
                                                            $paramCol = 2;
                                                            $valueCol = -1;
                                                            $subTable['base'] = $table;
                                                        }
                                                    }
                                                    elseif(count($table) >= 5 && $firstFieldNumericCount >= 3 && count($colCounter) == 1 && in_array($colCounter[0], [4])) // && isset($table[0][0]) && isset($table[1][0]) && (trim($table[0][0]) == '№' || trim($table[1][0]) == '№'))
                                                    {
                                                        if (trim($table[0][0]) == '№')
                                                        {
                                                            if (mb_strstr(mb_strtoupper(trim($table[0][1])), 'НАИМЕНОВАНИЕ'))
                                                            {
                                                                if (mb_strstr(mb_strtoupper(trim($table[0][3])), 'КОЛ-ВО'))
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 1;
                                                                    $valueCol = 3;
                                                                    $subTable['base'] = $table;
                                                                }
                                                                elseif (mb_strstr(mb_strtoupper(trim($table[0][2])), 'КОЛИЧЕСТВО'))
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 1;
                                                                    $valueCol = 2;
                                                                    $subTable['base'] = $table;
                                                                }
                                                                else
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 1;
                                                                    $valueCol = -1;
                                                                    $subTable['base'] = $table;
                                                                }
                                                            }
                                                            elseif (mb_strstr(mb_strtoupper(trim($table[0][2])), 'НАИМЕНОВАНИЕ'))
                                                            {
                                                                if (mb_strstr(mb_strtoupper(trim($table[0][3])), 'КОЛ-ВО') || mb_strstr(mb_strtoupper(trim($table[0][3])), 'КОЛИЧЕСТВО'))
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 2;
                                                                    $valueCol = 3;
                                                                    $subTable['base'] = $table;
                                                                    //dump('fileName: '.$fileName, $table);
                                                                }
                                                                else
                                                                {
                                                                    $startRow = 1;
                                                                    $paramCol = 2;
                                                                    $valueCol = -1;
                                                                    $subTable['base'] = $table;
                                                                }
                                                                //dump('fileName: '.$fileName, $table);
                                                            }
                                                            
                                                        }
                                                        elseif(isset($table[2][0]) && trim($table[2][0]) == '№' && isset($table[2][2]) && isset($table[2][3]))
                                                        {
                                                            $startRow = 3;
                                                            $paramCol = 2;
                                                            $valueCol = 3;
                                                            $subTable['base'] = $table;
                                                        }
                                                        elseif(isset($table[1][0]) && trim($table[1][0]) == '№' && mb_strstr(mb_strtoupper(trim($table[1][2])), 'НАИМЕНОВАНИЕ'))
                                                        {
                                                            if (mb_strstr(mb_strtoupper(trim($table[1][3])), 'КОЛ-ВО') || mb_strstr(mb_strtoupper(trim($table[1][3])), 'КОЛИЧЕСТВО'))
                                                            {
                                                                $startRow = 2;
                                                                $paramCol = 2;
                                                                $valueCol = 3;
                                                                $subTable['base'] = $table;
                                                            }
                                                            else
                                                            {
                                                                $startRow = 2;
                                                                $paramCol = 2;
                                                                $valueCol = -1;
                                                                $subTable['base'] = $table;
                                                            }
                                                        }
                                                        elseif(mb_strtoupper(trim($table[0][0])) == 'ПОЗ.')
                                                        {
                                                            if (mb_strtoupper(trim($table[0][3])) == 'КОЛ-ВО')
                                                            {
                                                                $startRow = 1;
                                                                $paramCol = 1;
                                                                $valueCol = 3;
                                                                $subTable['base'] = $table;
                                                            }
                                                            elseif(mb_strtoupper(trim($table[0][2])) == 'КОЛ-ВО')
                                                            {
                                                                //$isDebug = true;
                                                                $startRow = 1;
                                                                $paramCol = 1;
                                                                $valueCol = 2;
                                                                $subTable['base'] = $table;
                                                            }
                                                        }
                                                        elseif(mb_strtoupper(trim($table[0][0])) == '№ П/П' && mb_strtoupper(trim($table[0][1])) == 'МОДЕЛЬ')
                                                        {
                                                            $nTable = [];
                                                            foreach($table as $row)
                                                            {
                                                                $nTable[] =
                                                                [
                                                                    0 => $row[0],
                                                                    1 => $row[2].' '.$row[1],
                                                                    2 => $row[3],
                                                                ];
                                                            }
                                                            $table = $nTable;
                                                            //$isDebug = true;
                                                            $startRow = 1;
                                                            $paramCol = 1;
                                                            $valueCol = 2;
                                                            $subTable['base'] = $table;
                                                        }
                                                        elseif(mb_strtoupper(trim($table[0][0])) == 'СТОИМОСТЬ ОБОРУДОВАНИЯ В ПРЕДЛАГАЕМОЙ КОМПЛЕКТАЦИИ')
                                                        {
                                                            //$isDebug = true;
                                                            $startRow = 2;
                                                            $paramCol = 1;
                                                            $valueCol = 3;

                                                            $keyType = 'base';
                                                            foreach($table as $row)
                                                            {
                                                                if (mb_strtoupper(trim($row[0])) == 'ВОЗМОЖНОСТИ ДОПОЛНИТЕЛЬНОГО ОСНАЩЕНИЯ')
                                                                {
                                                                    $keyType = 'dop';
                                                                }
                                                                else
                                                                {
                                                                    if ($keyType == 'dop') unset($row[3]);
                                                                    $subTable[$keyType][] = $row;
                                                                }
                                                            }
                                                        }
                                                        elseif(mb_strtoupper(trim($table[0][0])) == '№ П/П' && mb_strtoupper(trim($table[0][1])) == 'УЗЕЛ')
                                                        {
                                                            $compExclude = true;
                                                        }
                                                        elseif(mb_strtoupper(trim($table[0][0])) == 'NO.' && mb_strtoupper(trim($table[0][1])) == 'НАИМЕНОВАНИЕ')
                                                        {
                                                            //$isDebug = true;
                                                            $startRow = 1;
                                                            $paramCol = 1;
                                                            $valueCol = 2;
                                                            $subTable['base'] = $table;
                                                        }
                                                    }
                                                    elseif(1>2 && mb_strstr(mb_strtoupper(trim($table[0][0])), 'СТОИМОСТЬ ОБОРУДОВАНИЯ В ПРЕДЛАГАЕМОЙ КОМПЛЕКТАЦИИ') && count($subTable['base']) == 0 && isset($table[1]))
                                                    {
                                                        if (count($table[2]) == 3)
                                                        {
                                                            if (trim($table[1][0]) == '№')
                                                            {
                                                                if (mb_strtoupper(trim($table[1][2])) == 'КОЛ-ВО')
                                                                {
                                                                    //$isDebug = true;
                                                                    $startRow = 2;
                                                                    $paramCol = 1;
                                                                    $valueCol = 2;
                                                                    $subTable['base'] = $table;
                                                                }
                                                                else
                                                                {
                                                                    //$isDebug = true;
                                                                    $startRow = 2;
                                                                    $paramCol = 1;
                                                                    $valueCol = -1;
                                                                    $keyType = 'base';
                                                                    dump('fileName: '.$fileName, $table, $subTable);
                                                                    $subTable['base'] = [];
                                                                    $subTable['dop'] = [];
                                                                    foreach($table as $row)
                                                                    {
                                                                        if (isset($row[1]) && mb_strtoupper(trim($row[1])) == 'ДОПОЛНИТЕЛЬНЫЕ ОПЦИИ')
                                                                        {
                                                                            
                                                                            $keyType = 'dop';
                                                                        }
                                                                        else
                                                                        {
                                                                            //if (isset($row[1])) print_r($row);
                                                                            $subTable['dop'][] = $row;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            elseif (trim($table[2][0]) == '№')
                                                            {

                                                            }
                                                            else
                                                            {
                                                                //dump('fileName: '.$fileName, $table);
                                                            }
                                                            //if (mb_strtoupper(trim($table[1])))
                                                            
                                                        }
                                                        else
                                                        {
                                                            //dump('fileName: '.$fileName, $table);
                                                        }
                                                    }
                                                    else
                                                    {
                                                        
                                                        //$compExclude = true;
                                                    }
                                                }
                                            }

                                            if ($startRow >= 0 && $paramCol >= 0)
                                            {
                                                foreach($subTable as $compType => $tbls)
                                                {
                                                    foreach($tbls as $rowNum => $cols)
                                                    {
                                                        if ($rowNum >= $startRow)
                                                        {
                                                            if (isset($cols[$paramCol]) && $cols[$paramCol] <> '')
                                                            {
                                                                $compTables[$compType][] =
                                                                [
                                                                    'param' => $cols[$paramCol],
                                                                    'unit'  => (isset($cols[$unitCol]))  ? trim($cols[$unitCol])  : '',
                                                                    'value' => (isset($cols[$valueCol])) ? trim($cols[$valueCol]) : '',
                                                                ];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            elseif(!$compExclude)
                                            {
                                                //dump('fileName: '.$fileName, $tables);
                                            }

                                            if (count($compTables['base']) > 0)
                                            {
                                               //dump('fileName: '.$fileName, $compTables);
                                            }

                                            if (count($compTables['base']) > 0 && $isDebug)
                                            {
                                               //dump('fileName: '.$fileName, $compTables);
                                            }

                                            
                                            
                                            //dd('fileName: '.$fileName, 'comp:', $tables['pricing'], 'tech:', $tables['technical_specifications'], 'final:', $compTables);
                                        }


                                        //print_r('<pre> File:  '.$fileName.'</pre>');
                                        //dump('techTableNumber: '.$techTableNumber);
                                        //dump($tsTableData);
                                        //dump($tsTable, $tsTableData);

                                        $data[$fileName] =
                                        [
                                            'fileName'     => $fileName,
                                            'headers'      => $newHeaders,
                                            'model'        => $model,
                                            'manufName'    => $manufacturerName,
                                            'manufCountry' => $manufacturerCountry,
                                            'tech'         => $tsTableData,
                                            'comp'         => $compTables,
                                        ];
                                    }

                                    /*
                                    if (COUNT($tsTableData) == 1 && isset($tsTableData['NONE']) && count($tsTableData['NONE']) < 10)
                                    {
                                        //print_r('<pre> File:  '.$fileName.'</pre>');
                                        //dump($tsTable, $tsTableData);
                                    }
                                    elseif(count($tsTableData) == 0)
                                    {
                                        //print_r('<pre> File:  '.$fileName.'</pre>');
                                        //dump('techTableNumber: '.$techTableNumber);
                                        //dump($tsTable, $tsTableData);
                                    }
                                    elseif(count($tsTableData) == 0)
                                    {
                                        //print_r('<pre> File:  '.$fileName.'</pre>');
                                        //dump('techTableNumber: '.$techTableNumber);
                                        //dump($tsTableData);
                                        //dump($tsTable, $tsTableData);
                                    }
                                    */
                                }
                            }
                            else
                            {
                                //print_r('<pre> --> '.$tableTitle.'</pre>');
                            }

                            //dump($tableTitle);
                        }
                        else
                        {
                            //dump($tsTable);
                        }

                        //if (1>2 && $isExistTsTableData)
                        //{
                        //    print_r('<pre> File:  '.$fileName.'</pre>');
                        //    dump($tsTable);
                        //}

                        //break;


                        
                    }
                }
            }
        }

        

        foreach($data as $key => $row)
        {
            if (count($row['model']) == 2)
            {
                $data[$key]['model'] = [0 => $data[$key]['model'][1]];
            }
            elseif (count($row['model']) > 2)
            {
                unset($data[$key]);
            }
            elseif(isset($row['tech']['NONE']['Наименование']) || isset($row['tech']['NONE']['Основные параметры']) || isset($row['tech']['NONE']['Параметры:']))
            {
                unset($data[$key]);
            }
        }


        print '<pre>Общее кол-во файлов DOCX: '.$docxCount.'</pre>';

        print '<pre>Найдено файлов с техническими характеристиками: '.count($data).'</pre>';

        print '<pre>Заголовков: '.count($headersArr).'</pre>';

        print '<pre>--------------------------------------</pre>';

        
        

        /*
        $uniqManufs = [];
        $uniqModels = [];

        foreach($data as $row)
        {
            //if (count($row['comp']['base']) > 0)
            {
                //dd($row);
                $manufNameLower = mb_strtolower($row['manufName']);
                $uniqManufs[$manufNameLower] = 1;

                foreach($row['model'] as $modelName)
                {
                    $modelNameLower = mb_strtolower($modelName);
                    $uniqModels[$modelNameLower] = 1;
                }
            }
        }

        dd('manufs: '.count($uniqManufs), 'models: '.count($uniqModels));

        exit;
        */
        

        

        $dbLm = DB::connection('livemachines');
        $pdo  = $dbLm->getPdo();

        foreach($docxFilesArr as $fileName)
        {
            $dbFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_file` WHERE `dirty_file_name` = ?", [(string)$fileName]);

            if (count($dbFile) == 0)
            {
                $sql =
                "
                INSERT INTO `dirty_file`
                SET
                    `dirty_file_name` = ".$pdo->quote((string)$fileName)."
                ";
                $dbLm->insert($sql);
            }
        }

        


        $n = 1;
        ksort($manufArr);
        ksort($modelArr);
        ksort($groupsArr);
        foreach($groupsArr as $key => $val)
        {
            //print '<pre>'.$n.'. '.$val.'</pre>';
            $n++;
        }

        foreach($data as $row)
        {
            if (count($row['model']) == 0)
            {
                //dump($row['fileName'], $row['headers']);
            }
        }

        

        foreach($data as $t => $row)
        {
            if (count($row['comp']['base']) > 0)
            {
                $ext = false;
                $key = 'base';
                $dopArr = [];
                foreach($row['comp']['base'] as $z => $prm)
                {
                    if (mb_strstr(mb_strtoupper($prm['value']), '$') || mb_strstr(mb_strtoupper($prm['value']), '₽') || mb_strstr(mb_strtoupper($prm['value']), '€') || mb_strstr(mb_strtoupper($prm['value']), '¥') || mb_strstr(mb_strtoupper($prm['value']), 'РУБ.'))
                    {
                        $data[$t]['comp']['base'][$z]['value'] = '';
                    }
                    
                    $txt = mb_strtoupper($prm['param']);

                    if
                    (   $txt == 'ДОПОЛНИТЕЛЬНЫЕ ОПЦИИ' ||
                        $txt == 'ДОПОЛНИТЕЛЬНОЕ ОСНАЩЕНИЕ:' ||
                        $txt == 'ДОПОЛНИТЕЛЬНОЕ ОСНАЩЕНИЕ' ||
                        $txt == 'ДОПОЛНИТЕЛЬНАЯ КОМПЛЕКТАЦИЯ' ||
                        $txt == 'ВОЗМОЖНЫЕ ДОПОЛНИТЕЛЬНЫЕ ОПЦИИ' ||
                        $txt == 'ДОПОЛНИТЕЛЬНЫЕ ОПЦИИ:' ||
                        $txt == 'ВОЗМОЖНЫЕ ДОПОЛНИТЕЛЬНЫЕ ОПЦИИ:' ||
                        $txt == 'ДОПОЛНИТЕЛЬНАЯ ОСНАЩЕНИЕ'
                    )
                    {
                        $ext = true;
                        $key = 'dop';
                        unset($data[$t]['comp']['base'][$z]);
                    }
                    elseif (mb_strstr(mb_strtoupper($prm['param']), 'СТОИМОСТЬ'))
                    {
                        unset($data[$t]['comp']['base'][$z]);
                    }
                    elseif ($key == 'dop')
                    {
                        $dopArr[] = $prm;
                    }
                }

                if ($ext)
                {
                    $data[$t]['comp']['dop'] = $dopArr;
                }
                
                foreach($data[$t]['comp']['dop'] as $z => $prm)
                {
                    if (mb_strstr(mb_strtoupper($prm['value']), '$') || mb_strstr(mb_strtoupper($prm['value']), '₽') || mb_strstr(mb_strtoupper($prm['value']), '€') || mb_strstr(mb_strtoupper($prm['value']), '¥') || mb_strstr(mb_strtoupper($prm['value']), 'РУБ.'))
                    {
                        $data[$t]['comp']['dop'][$z]['value'] = '';
                    }
                }
            }
        }




        
        foreach($data as $row)
        {
            $dbFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_file` WHERE `dirty_file_name` = ?", [(string)$row['fileName']]);

            if (count($dbFile) > 0)
            {
                $fileId = (int)$dbFile['dirty_file_id'];
            }
            else
            {
                $sql =
                "
                INSERT INTO `dirty_file`
                SET
                    `dirty_file_name` = ".$pdo->quote((string)$row['fileName'])."
                ";
                $dbLm->insert($sql);
                $fileId = $pdo->lastInsertId();
            }

            if ($fileId > 0)
            {
                $modelId = 0;
                foreach((array)$row['model'] as $modelName)
                {
                    $modelName = trim($modelName);
                    if (mb_strlen($modelName) > 0)
                    {
                        $dbModel = (array)$dbLm->selectOne("SELECT * FROM `dirty_model` WHERE `dirty_model_name` = ?", [(string)$modelName]);
                        
                        if (count($dbModel) > 0)
                        {
                            $modelId = (int)$dbModel['dirty_model_id'];
                        }
                        else
                        {
                            $sql =
                            "
                            INSERT INTO `dirty_model`
                            SET
                                `dirty_model_name`        = ".$pdo->quote((string)$modelName).",
                                `dirty_model_add_user_id` = 0,
                                `dirty_model_add_date`    = 0
                            ";
                            $dbLm->insert($sql);
                            $modelId = $pdo->lastInsertId();
                        }

                        if ($modelId > 0)
                        {
                            $dbModelFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_model_file` WHERE `dirty_model_file_dirty_model_id` = ? AND `dirty_model_file_dirty_file_id` = ?", [(int)$modelId, (int)$fileId]);
                            if (count($dbModelFile) == 0)
                            {
                                $sql =
                                "
                                INSERT INTO `dirty_model_file`
                                SET
                                    `dirty_model_file_dirty_model_id` = ".$pdo->quote((int)$modelId).",
                                    `dirty_model_file_dirty_file_id`  = ".$pdo->quote((int)$fileId)."
                                ";
                                $dbLm->insert($sql);
                            }
                        }
                    }
                }

                $countryId   = 0;
                $countryName = trim($row['manufCountry']);
                if (mb_strlen($countryName) > 0)
                {
                    $dbCountry = (array)$dbLm->selectOne("SELECT * FROM `dirty_country` WHERE `dirty_country_name` = ?", [(string)$countryName]);
                    
                    if (count($dbCountry) > 0)
                    {
                        $countryId = (int)$dbCountry['dirty_country_id'];
                    }
                    else
                    {
                        $sql =
                        "
                        INSERT INTO `dirty_country`
                        SET
                            `dirty_country_name` = ".$pdo->quote((string)$countryName)."
                        ";
                        $dbLm->insert($sql);
                        $countryId = $pdo->lastInsertId();
                    }

                    if ($countryId > 0)
                    {
                        $dbCountryFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_country_file` WHERE `dirty_country_file_dirty_country_id` = ? AND `dirty_country_file_dirty_file_id` = ?", [(int)$countryId, (int)$fileId]);
                        if (count($dbCountryFile) == 0)
                        {
                            $sql =
                            "
                            INSERT INTO `dirty_country_file`
                            SET
                                `dirty_country_file_dirty_country_id` = ".$pdo->quote((int)$countryId).",
                                `dirty_country_file_dirty_file_id`  = ".$pdo->quote((int)$fileId)."
                            ";
                            $dbLm->insert($sql);
                        }
                    }
                }

                
                $manufId   = 0;
                $manufName = trim($row['manufName']);
                if (mb_strlen($manufName) > 0)
                {
                    $dbManuf = (array)$dbLm->selectOne("SELECT * FROM `dirty_manuf` WHERE `dirty_manuf_name` = ?", [(string)$manufName]);
                    if (count($dbManuf) > 0)
                    {
                        $manufId = (int)$dbManuf['dirty_manuf_id'];
                    }
                    else
                    {
                        $sql =
                        "
                        INSERT INTO `dirty_manuf`
                        SET
                            `dirty_manuf_name` = ".$pdo->quote((string)$manufName)."
                        ";
                        $dbLm->insert($sql);
                        $manufId = $pdo->lastInsertId();
                    }

                    if ($manufId > 0)
                    {
                        $dbManufFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_manuf_file` WHERE `dirty_manuf_file_dirty_manuf_id` = ? AND `dirty_manuf_file_dirty_file_id` = ?", [(int)$manufId, (int)$fileId]);
                        if (count($dbManufFile) == 0)
                        {
                            $sql =
                            "
                            INSERT INTO `dirty_manuf_file`
                            SET
                                `dirty_manuf_file_dirty_manuf_id` = ".$pdo->quote((int)$manufId).",
                                `dirty_manuf_file_dirty_file_id`  = ".$pdo->quote((int)$fileId)."
                            ";
                            $dbLm->insert($sql);
                        }
                    }
                }

                

                if ($countryId > 0 && $manufId > 0)
                {
                    $dbManufCountry = (array)$dbLm->selectOne("SELECT * FROM `dirty_manuf_country` WHERE `dirty_manuf_country_dirty_manuf_id` = ? AND `dirty_manuf_country_dirty_country_id` = ?", [(int)$manufId, (int)$countryId]);
                    if (count($dbManufCountry) == 0)
                    {
                        $sql =
                        "
                        INSERT INTO `dirty_manuf_country`
                        SET
                            `dirty_manuf_country_dirty_manuf_id`   = ".$pdo->quote((int)$manufId).",
                            `dirty_manuf_country_dirty_country_id` = ".$pdo->quote((int)$countryId).",
                            `dirty_manuf_country_add_user_id`      = 0,
                            `dirty_manuf_country_add_date`         = 0
                        ";
                        $dbLm->insert($sql);
                    }
                }
            }

            
            foreach((array)$row['tech'] as $groupName => $params)
            {
                
                $groupId = 0;
                $groupName = trim($groupName);
                if ($groupName == 'NONE' || mb_strlen($groupName) == 0)
                {
                    $groupId = 0;
                }
                else
                {
                    $dbGroup = (array)$dbLm->selectOne("SELECT * FROM `dirty_group` WHERE `dirty_group_name` = ? AND `dirty_group_dirty_type_id` = 1", [(string)$groupName]);
                    if (count($dbGroup) > 0)
                    {
                        $groupId = (int)$dbGroup['dirty_group_id'];
                    }
                    else
                    {
                        $sql =
                        "
                        INSERT INTO `dirty_group`
                        SET
                            `dirty_group_name`          = ".$pdo->quote((string)$groupName).",
                            `dirty_group_dirty_type_id` = 1,
                            `dirty_group_add_user_id`   = 0,
                            `dirty_group_add_date`      = 0
                        ";
                        $dbLm->insert($sql);
                        $groupId = $pdo->lastInsertId();
                    }

                    if ($groupId == 202)
                    {
                        //dd('STOP', 'FILE_ID: '.$fileId);
                    }
                }

                
                if ($groupId > 0)
                {
                    $dbGroupFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_group_file` WHERE `dirty_group_file_dirty_group_id` = ? AND `dirty_group_file_dirty_file_id` = ?", [(int)$groupId, (int)$fileId]);
                    if (count($dbGroupFile) == 0)
                    {
                        $sql =
                        "
                        INSERT INTO `dirty_group_file`
                        SET
                            `dirty_group_file_dirty_group_id` = ".$pdo->quote((int)$groupId).",
                            `dirty_group_file_dirty_file_id`  = ".$pdo->quote((int)$fileId)."
                        ";
                        $dbLm->insert($sql);
                    }
                }
                
                
                foreach($params as $paramName => $paramSubRows)
                {
                    foreach($paramSubRows as $paramSubRow)
                    {
                        $paramName  = trim($paramName);
                        $paramUnit  = trim($paramSubRow['unit']);
                        $paramValue = trim($paramSubRow['value']);

                        $paramNameId = 0;

                        if (mb_strlen($paramName) > 0)
                        {
                            $dbParamName = (array)$dbLm->selectOne("SELECT * FROM `dirty_param_name` WHERE `dirty_param_name_value` = ? AND `dirty_param_name_dirty_type_id` = 1", [(string)$paramName]);
                            if (count($dbParamName) > 0)
                            {
                                $paramNameId = (int)$dbParamName['dirty_param_name_id'];
                            }
                            else
                            {
                                $sql =
                                "
                                INSERT INTO `dirty_param_name`
                                SET
                                    `dirty_param_name_value`         = ".$pdo->quote((string)$paramName).",
                                    `dirty_param_name_dirty_type_id` = 1
                                ";
                                $dbLm->insert($sql);
                                $paramNameId = $pdo->lastInsertId();
                            }
                        }
                        
                        $paramUnitId = 0;

                        if ($paramNameId > 0 && mb_strlen($paramUnit) > 0)
                        {
                            $dbParamUnit = (array)$dbLm->selectOne("SELECT * FROM `dirty_param_unit` WHERE `dirty_param_unit_value` = ? AND `dirty_param_unit_dirty_type_id` = 1", [(string)$paramUnit]);
                            if (count($dbParamUnit) > 0)
                            {
                                $paramUnitId = (int)$dbParamUnit['dirty_param_unit_id'];
                            }
                            else
                            {
                                $sql =
                                "
                                INSERT INTO `dirty_param_unit`
                                SET
                                    `dirty_param_unit_value`         = ".$pdo->quote((string)$paramUnit).",
                                    `dirty_param_unit_dirty_type_id` = 1
                                ";
                                $dbLm->insert($sql);
                                $paramUnitId = $pdo->lastInsertId();
                            }
                        }

                        $paramValueId = 0;

                        if ($paramNameId > 0 && mb_strlen($paramValue) > 0)
                        {
                            $dbParamValue = (array)$dbLm->selectOne("SELECT * FROM `dirty_param_value` WHERE `dirty_param_value_value` = ? AND `dirty_param_value_dirty_type_id` = 1", [(string)$paramValue]);
                            if (count($dbParamValue) > 0)
                            {
                                $paramValueId = (int)$dbParamValue['dirty_param_value_id'];
                            }
                            else
                            {
                                $sql =
                                "
                                INSERT INTO `dirty_param_value`
                                SET
                                    `dirty_param_value_value`         = ".$pdo->quote((string)$paramValue).",
                                    `dirty_param_value_dirty_type_id` = 1
                                ";
                                $dbLm->insert($sql);
                                $paramValueId = $pdo->lastInsertId();
                            }
                        }

                        if ($paramNameId > 0)
                        {
                            $dbParam = (array)$dbLm->selectOne("SELECT * FROM `dirty_param` WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_param_unit_id` = ? AND `dirty_param_dirty_param_value_id` = ? AND `dirty_param_dirty_type_id` = 1 AND `dirty_param_dirty_group_id` = ? AND `dirty_param_dirty_file_id` = ?", [(int)$paramNameId, (int)$paramUnitId, (int)$paramValueId, (int)$groupId, (int)$fileId]);
                            if (count($dbParam) > 0)
                            {
                                $paramId = (int)$dbParam['dirty_param_id'];
                            }
                            else
                            {
                                $sql =
                                "
                                INSERT INTO `dirty_param`
                                SET
                                    `dirty_param_dirty_param_name_id`  = ".$pdo->quote((int)$paramNameId).",
                                    `dirty_param_dirty_param_unit_id`  = ".$pdo->quote((int)$paramUnitId).",
                                    `dirty_param_dirty_param_value_id` = ".$pdo->quote((int)$paramValueId).",
                                    `dirty_param_dirty_group_id`       = ".$pdo->quote((int)$groupId).",
                                    `dirty_param_dirty_file_id`        = ".$pdo->quote((int)$fileId).",
                                    `dirty_param_dirty_type_id`        = 1
                                ";
                                $dbLm->insert($sql);
                                $paramId = $pdo->lastInsertId();
                            }
                        }
                    }
                }

                foreach((array)$row['comp'] as $compType => $params)
                {
                    if (count($params) > 0)
                    {
                        $groupId   = 0;
                        $groupName = '';

                        if ($compType == 'base')
                        {
                            $groupName = 'ОСНОВНАЯ КОМПЛЕКТАЦИЯ';
                        }
                        elseif ($compType == 'dop')
                        {
                            $groupName = 'ДОПОЛНИТЕЛЬНАЯ КОМПЛЕКТАЦИЯ';
                        }

                        $dbGroup = (array)$dbLm->selectOne("SELECT * FROM `dirty_group` WHERE `dirty_group_name` = ? AND `dirty_group_dirty_type_id` = 2", [(string)$groupName]);
                        if (count($dbGroup) > 0)
                        {
                            $groupId = (int)$dbGroup['dirty_group_id'];
                        }
                        else
                        {
                            $sql =
                            "
                            INSERT INTO `dirty_group`
                            SET
                                `dirty_group_name`          = ".$pdo->quote((string)$groupName).",
                                `dirty_group_dirty_type_id` = 2,
                                `dirty_group_add_user_id`   = 0,
                                `dirty_group_add_date`      = 0
                            ";
                            $dbLm->insert($sql);
                            $groupId = $pdo->lastInsertId();
                        }

                        if ($groupId > 0)
                        {
                            if ($groupId > 0)
                            {
                                $dbGroupFile = (array)$dbLm->selectOne("SELECT * FROM `dirty_group_file` WHERE `dirty_group_file_dirty_group_id` = ? AND `dirty_group_file_dirty_file_id` = ?", [(int)$groupId, (int)$fileId]);
                                if (count($dbGroupFile) == 0)
                                {
                                    $sql =
                                    "
                                    INSERT INTO `dirty_group_file`
                                    SET
                                        `dirty_group_file_dirty_group_id` = ".$pdo->quote((int)$groupId).",
                                        `dirty_group_file_dirty_file_id`  = ".$pdo->quote((int)$fileId)."
                                    ";
                                    $dbLm->insert($sql);
                                }
                            }

                            foreach($params as $param)
                            {
                                $paramNameId = 0;

                                $paramName  = trim($param['param']);
                                $paramUnit  = trim($param['unit']);
                                $paramValue = trim($param['value']);

                                if (mb_strlen($paramName) > 0)
                                {
                                    $dbParamName = (array)$dbLm->selectOne("SELECT * FROM `dirty_param_name` WHERE `dirty_param_name_value` = ? AND `dirty_param_name_dirty_type_id` = 2", [(string)$paramName]);
                                    if (count($dbParamName) > 0)
                                    {
                                        $paramNameId = (int)$dbParamName['dirty_param_name_id'];
                                    }
                                    else
                                    {
                                        $sql =
                                        "
                                        INSERT INTO `dirty_param_name`
                                        SET
                                            `dirty_param_name_value`         = ".$pdo->quote((string)$paramName).",
                                            `dirty_param_name_dirty_type_id` = 2
                                        ";
                                        $dbLm->insert($sql);
                                        $paramNameId = $pdo->lastInsertId();
                                    }
                                }

                                $paramUnitId = 0;

                                if ($paramNameId > 0 && mb_strlen($paramUnit) > 0)
                                {
                                    $dbParamUnit = (array)$dbLm->selectOne("SELECT * FROM `dirty_param_unit` WHERE `dirty_param_unit_value` = ? AND `dirty_param_unit_dirty_type_id` = 2", [(string)$paramUnit]);
                                    if (count($dbParamUnit) > 0)
                                    {
                                        $paramUnitId = (int)$dbParamUnit['dirty_param_unit_id'];
                                    }
                                    else
                                    {
                                        $sql =
                                        "
                                        INSERT INTO `dirty_param_unit`
                                        SET
                                            `dirty_param_unit_value`         = ".$pdo->quote((string)$paramUnit).",
                                            `dirty_param_unit_dirty_type_id` = 2
                                        ";
                                        $dbLm->insert($sql);
                                        $paramUnitId = $pdo->lastInsertId();
                                    }
                                }

                                $paramValueId = 0;

                                if ($paramNameId > 0 && mb_strlen($paramValue) > 0 && mb_strlen($paramValue) <= 255)
                                {
                                    $dbParamValue = (array)$dbLm->selectOne("SELECT * FROM `dirty_param_value` WHERE `dirty_param_value_value` = ? AND `dirty_param_value_dirty_type_id` = 2", [(string)$paramValue]);
                                    if (count($dbParamValue) > 0)
                                    {
                                        $paramValueId = (int)$dbParamValue['dirty_param_value_id'];
                                    }
                                    else
                                    {
                                        $sql =
                                        "
                                        INSERT INTO `dirty_param_value`
                                        SET
                                            `dirty_param_value_value`         = ".$pdo->quote((string)$paramValue).",
                                            `dirty_param_value_dirty_type_id` = 2
                                        ";
                                        $dbLm->insert($sql);
                                        $paramValueId = $pdo->lastInsertId();
                                    }
                                }

                                if ($paramNameId > 0)
                                {
                                    $dbParam = (array)$dbLm->selectOne("SELECT * FROM `dirty_param` WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_param_unit_id` = ? AND `dirty_param_dirty_param_value_id` = ? AND `dirty_param_dirty_type_id` = 2 AND `dirty_param_dirty_group_id` = ? AND `dirty_param_dirty_file_id` = ?", [(int)$paramNameId, (int)$paramUnitId, (int)$paramValueId, (int)$groupId, (int)$fileId]);
                                    if (count($dbParam) > 0)
                                    {
                                        $paramId = (int)$dbParam['dirty_param_id'];
                                    }
                                    else
                                    {
                                        $sql =
                                        "
                                        INSERT INTO `dirty_param`
                                        SET
                                            `dirty_param_dirty_param_name_id`  = ".$pdo->quote((int)$paramNameId).",
                                            `dirty_param_dirty_param_unit_id`  = ".$pdo->quote((int)$paramUnitId).",
                                            `dirty_param_dirty_param_value_id` = ".$pdo->quote((int)$paramValueId).",
                                            `dirty_param_dirty_group_id`       = ".$pdo->quote((int)$groupId).",
                                            `dirty_param_dirty_file_id`        = ".$pdo->quote((int)$fileId).",
                                            `dirty_param_dirty_type_id`        = 2
                                        ";
                                        $dbLm->insert($sql);
                                        $paramId = $pdo->lastInsertId();
                                    }
                                }
                            }
                        }
                    }
                }

                //dd($row);
                //break;
            }
            
            

            //dump('manufs: '.count($uniqManufs).' | models: '.count($uniqModels)); 
            
            //dump($row, $fileId);

            //break;
        }


        dd('READY OK'); 
        

        

        


        return false;

















        $pdo = DB::getPdo();

        // ЗАПИСЬ НАЗВАНИЯ ФАЙЛОВ И JSON МАССИВОВ
        /*
        $directory = public_path().'/json_files';
        $scanned_directory = array_diff(scandir($directory), array('..', '.'));

        foreach($scanned_directory as $jsonFileName)
        {
            $filePath = public_path().'/json_files/'.$jsonFileName;
            if (file_exists($filePath))
            {
                $jsonString  = trim(file_get_contents($filePath));
                $jsonData    = json_decode($jsonString, true);
                $pdfFileName = trim($jsonData['metadata']['file_name']);

                $files = DB::select("SELECT * FROM `dirty_file` WHERE `dirty_file_name` = ?", [(string)$jsonFileName]);

                print $pdfFileName."<br>";

                if (count($files) == 0)
                {
                    $sql =
                    "
                    INSERT INTO `dirty_file`
                    SET
                        `dirty_file_name` = ".$pdo->quote((string)$pdfFileName).",
                        `dirty_file_json` = ".$pdo->quote((string)$jsonString)."
                    ";
                    DB::insert($sql);
                    $fileId = $pdo->lastInsertId();
                }
                else
                {
                    $fileId = $files[0]->dirty_file_id;
                    $json   = json_decode($files[0]->dirty_file_json, true);
                }
            }
        }
        */

        $exclude =
        [
            '"Велам" КП 24.01.2022 г..pdf',
            '16.30.pdf',
            '18. ТКП Robbi (аналог GU).pdf',
            '20170601_ТКП на ролики_ЗЭТО.pdf',
            '20170704_ТКП_Lissmac_УНИКУМ.pdf',
            '20171205_ТКП_вставки для люнета_АРЕОПАГ.pdf',
            '20171020_ТКП_SEYI SN-1_25_45_110_ЗЭТО.pdf',
        ];

        $excludeNoRecognition =
        [
            '1310ТКП GA-2600.pdf',
            '1310ТКП  GA-2600.pdf',
            '18. ТКП Robbi (аналог GU).pdf', // scan
            '20160816_ТКП_Goodway GA-2000_2600_2800.pdf',
            '20160816_ТКП_Goodway GA-3300.pdf',
            '20170124_ТКП_Нева-Феррит_GLS-1500LM.pdf',
            '20170130_ТКП_Титан_GLS-1500LM.pdf',
            '20170214_ТКП_GOODWAY GA-2000M_ЗАСЛОН.pdf',
            '20170905_ТКП_DCM-3213_АКОНИТ.pdf',
            '20170906_ТКП_AWEA AF-1000_ЛПМ.pdf',
            '20171005_ТКП_AWEA AF-650_БКО.pdf',
            '20171015_ТКП_Dah Lih MCH-800_ЗЭТО.pdf',
            '20180130_ТКП_AWEA AF-1000_ЛПМ.pdf',
            '20180220_ТКП_LVD_Strippit P-1212_ЗАСЛОН.pdf',
            '20180220_ТКП_VD Dyna-пресс 24_12_ЗАСЛОН.pdf',
            '20180220_ТКП_А-13Т154333_СИГНАЛ.pdf',
            '20180226_ТКП_СЕМАТ Sk4030P_ОКЕАНПРИБОР руб.pdf',
            '20180227_ТКП AWEA BL3018FM_ЗЭТО.pdf',
            '20180314_ТКП_SEYI SN-1_25_45_110_стальком.pdf',
            '20180608_ТКП_AWEA AF-1000_Автобаки.pdf',
            '20190227_ТКП_EVERISING_S-300HB_ПТЗ.pdf',
            '20180615_ТКП_KMTC KiMi A-2_ЗЭТО.pdf',
            '20180620_ТКП_АМЗ NV-850_БКО.pdf',
            '20180620_ТКП_АМЗ А-6ВА102_15_бар_накладной стол_БКО.pdf',
            '20180620_ТКП_АМЗ А-6ВА102_15_бар_накладной стол_ЗЭТО.pdf',
        ];

        // ЗАПИСЬ ПАРАМЕТРОВ, ЕДИНИЦ ИЗМЕРЕНИЙ И ЗНАЧЕНИЙ
        $files = DB::select("SELECT * FROM `dirty_file` WHERE `dirty_file_remove` = 0");
        
        $allFiles = [];

        $tableTech = [];

        foreach($files as $file)
        {
            $fileId   = $file->dirty_file_id;
            $fileName = $file->dirty_file_name;

            if (!in_array($fileName, $exclude) && !in_array($fileName, $excludeNoRecognition))
            {
                $data = json_decode($file->dirty_file_json, true);

                $tables = (array)$data['tables'];

                $allFiles[$fileName] = $tables;

                foreach($tables as $table)
                {
                    $title = trim($table['head_title']);
                    $titleLower = mb_strtolower($title);
                    $titleLower = str_replace(".", " ",  $titleLower);
                    $titleLower = str_replace("  ", " ", $titleLower);

                    $isTech = false;
                    
                    if (mb_strstr($titleLower, "технические характеристики") && count($table['data']))
                    {
                        $isTech = true;
                    }

                    if (!$isTech)
                    {
                        foreach($table as $row)
                        {
                            //dump($row);
                            foreach($row as $subRow)
                            {
                                //dump($subRow);
                                foreach($subRow as $param)
                                {
                                    $paramLower = mb_strtolower($param);
                                    $paramLower = str_replace(".", " ",  $paramLower);
                                    $paramLower = str_replace("  ", " ", $paramLower);

                                    if (in_array($paramLower, [
                                        'максимальный диаметр обработки',
                                        'система чпу',
                                        'основные параметры обработки',
                                        'наибольший диаметр устанавливаемой детали, мм',
                                        'максимальный диаметр устанавливаемой детали, мм',
                                        'основные технологические возможности',
                                        'усилие, тонн',
                                        'диаметр прутка',
                                    ]))
                                    {
                                        $isTech = true;
                                    }
                                }
                            }
                            break;
                        }
                    }

                    if ($isTech)
                    {
                        if (!isset($tableTech[$fileName])) $tableTech[$fileName] = [];
                        $tableTech[$fileName][] = $table['data'];
                    }
                }
            }
        }

        print "Найдено файлов: ".(count($allFiles)+count($excludeNoRecognition))."<br>";

        print "Найдено файлов с техническими характеристиками: ".count($tableTech)."<br>";

        //dump($tableTech);

        $noRecogTech = [];

        foreach($allFiles as $fileName => $tables)
        {
            if (!isset($tableTech[$fileName]))
            {
                if (count($tables) == 1 && trim($tables[0]['head_title']) == 'Наименование')
                {
                    print "'".$fileName."',<br>";
                }
                else
                $year = mb_substr($fileName, 0, 4);
                //if ((is_numeric($year) && $year > 2018) || !is_numeric($year))
                {
                    $noRecogTech[$fileName] = $tables;
                }
            }
        }

        print "Не удалось распознать: ".count($noRecogTech)."<br>";

        foreach($noRecogTech as $fileName => $tables)
        {
            if (!isset($tableTech[$fileName]))
            {
                print $fileName."<br>";

                $json = $allFiles[$fileName];

                dump($fileName, $tables);

                break;
            }
        }


        /*
        //$currentLocale = app()->getLocale();
        //dump($currentLocale);
        return view('json', [
            'title'       => 'Adoxa - Главная страница',
            'description' => 'Добро пожаловать в Adoxa - ваш новый проект на Laravel',
            'features'    => [
                'Laravel '.app()->version(),
                'PHP '.PHP_VERSION,
                'MySQL 8.0',
                'Redis',
                'Docker',
                'Nginx'
            ]
        ]);
        */
    }


    function removeDuplicatePhrases($string)
    {
        // Разбиваем строку на слова
        $words = preg_split('/\s+/', trim($string));
        
        if (count($words) < 2) {
            return $string;
        }
        
        // Ищем повторяющиеся последовательности
        $result = [];
        $i = 0;
        
        while ($i < count($words)) {
            $foundDuplicate = false;
            
            // Проверяем последовательности разной длины
            for ($len = 1; $len <= floor((count($words) - $i) / 2); $len++) {
                $sequence1 = array_slice($words, $i, $len);
                $sequence2 = array_slice($words, $i + $len, $len);
                
                if ($sequence1 == $sequence2) {
                    // Нашли дубликат, добавляем только одну копию
                    $result = array_merge($result, $sequence1);
                    $i += $len * 2; // Пропускаем обе копии
                    $foundDuplicate = true;
                    break;
                }
            }
            
            if (!$foundDuplicate) {
                // Не нашли дубликат, добавляем текущее слово
                $result[] = $words[$i];
                $i++;
            }
        }
        
        return implode(' ', $result);
    }

    
}
