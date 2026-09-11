<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IranLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'تهران', 'slug' => 'thran', 'cities' => [['تهران', 'thran-city'], ['ری', 'ry'], ['شمیرانات', 'shmyranat'], ['شهریار', 'shahriar'], ['اسلامشهر', 'eslamshahr'], ['ورامین', 'varamin'], ['قدس', 'qods'], ['ملارد', 'malard'], ['پاکدشت', 'pakdasht'], ['دماوند', 'damavand']]],
            ['name' => 'اصفهان', 'slug' => 'asfhan', 'cities' => [['اصفهان', 'asfhan-city'], ['کاشان', 'kashan'], ['خمینی‌شهر', 'khomeyni-shahr'], ['نجف‌آباد', 'najafabad'], ['شاهین‌شهر', 'shahinshahr'], ['شهرضا', 'shahreza'], ['فولادشهر', 'fuladshahr'], ['مبارکه', 'mobarakeh'], ['آران و بیدگل', 'aran-bidgol'], ['زرین‌شهر', 'zarrinshahr']]],
            ['name' => 'یزد', 'slug' => 'yzd', 'cities' => [['یزد', 'yzd-city'], ['میبد', 'mybd'], ['اردکان', 'ardakan'], ['بافق', 'bafq'], ['مهریز', 'mehriz'], ['ابرکوه', 'abarkuh'], ['اشکذر', 'ashkezar'], ['تفت', 'taft'], ['هرات', 'harat'], ['زارچ', 'zarch']]],
            ['name' => 'خراسان رضوی', 'slug' => 'khrasan-rdoy', 'cities' => [['مشهد', 'mshhd'], ['نیشابور', 'nyshabor'], ['سبزوار', 'sabzevar'], ['تربت حیدریه', 'torbat-heydariyeh'], ['قوچان', 'quchan'], ['کاشمر', 'kashmar'], ['تربت جام', 'torbat-jam'], ['چناران', 'chenaran'], ['گناباد', 'gonabad'], ['سرخس', 'sarakhs']]],
            ['name' => 'فارس', 'slug' => 'fars', 'cities' => [['شیراز', 'shyraz'], ['مرودشت', 'mrodsht'], ['جهرم', 'jahrom'], ['فسا', 'fasa'], ['کازرون', 'kazerun'], ['لار', 'lar'], ['آباده', 'abadeh'], ['داراب', 'darab'], ['فیروزآباد', 'firuzabad'], ['لامرد', 'lamerd']]],
            ['name' => 'البرز', 'slug' => 'albrz', 'cities' => [['کرج', 'krg'], ['نظرآباد', 'nthrabad'], ['فردیس', 'fardis'], ['کمال‌شهر', 'kamalshahr'], ['محمدشهر', 'mohammadshahr'], ['ماهدشت', 'mahdhasht'], ['مشکین‌دشت', 'meshkindasht'], ['هشتگرد', 'hashtgerd'], ['چهارباغ', 'chaharbagh'], ['اشتهارد', 'eshtehard']]],
            ['name' => 'آذربایجان شرقی', 'slug' => 'azarbayjan-sharqi', 'cities' => [['تبریز', 'tabriz'], ['مراغه', 'maragheh'], ['مرند', 'marand'], ['میانه', 'mianeh'], ['اهر', 'ahar'], ['بناب', 'bonab'], ['سراب', 'sarab'], ['آذرشهر', 'azarshahr'], ['هادیشهر', 'hadishahr'], ['عجب‌شیر', 'ajabshir']]],
            ['name' => 'آذربایجان غربی', 'slug' => 'azarbayjan-gharbi', 'cities' => [['ارومیه', 'urmia'], ['خوی', 'khoy'], ['بوکان', 'bukan'], ['مهاباد', 'mahabad'], ['میاندوآب', 'miandoab'], ['سلماس', 'salmas'], ['پیرانشهر', 'piranshahr'], ['نقده', 'naghadeh'], ['تکاب', 'takab'], ['ماکو', 'maku']]],
            ['name' => 'خوزستان', 'slug' => 'khuzestan', 'cities' => [['اهواز', 'ahvaz'], ['دزفول', 'dezful'], ['آبادان', 'abadan'], ['بندر ماهشهر', 'bandar-mahshahr'], ['خرمشهر', 'khorramshahr'], ['اندیمشک', 'andimeshk'], ['ایذه', 'izeh'], ['بهبهان', 'behbahan'], ['شوشتر', 'shushtar'], ['مسجد سلیمان', 'masjed-soleyman']]],
            ['name' => 'مازندران', 'slug' => 'mazandaran', 'cities' => [['ساری', 'sari'], ['بابل', 'babol'], ['آمل', 'amol'], ['قائم‌شهر', 'qaemshahr'], ['بهشهر', 'behshahr'], ['چالوس', 'chalus'], ['نکا', 'neka'], ['بابلسر', 'babolsar'], ['تنکابن', 'tonekabon'], ['نوشهر', 'nowshahr']]],
            ['name' => 'گیلان', 'slug' => 'gilan', 'cities' => [['رشت', 'rasht'], ['بندر انزلی', 'bandar-anzali'], ['لاهیجان', 'lahijan'], ['لنگرود', 'langarud'], ['هشتپر', 'hashtpar'], ['آستارا', 'astara'], ['صومعه‌سرا', 'sowme-eh-sara'], ['آستانه اشرفیه', 'astaneh-ashrafiyeh'], ['رودسر', 'rudsar'], ['فومن', 'fuman']]],
            ['name' => 'کرمان', 'slug' => 'kerman', 'cities' => [['کرمان', 'kerman-city'], ['سیرجان', 'sirjan'], ['رفسنجان', 'rafsanjan'], ['جیرفت', 'jiroft'], ['بم', 'bam'], ['زرند', 'zarand'], ['کهنوج', 'kahnuj'], ['شهربابک', 'shahr-e-babak'], ['بافت', 'baft'], ['بردسیر', 'bardsir']]],
            ['name' => 'کرمانشاه', 'slug' => 'kermanshah', 'cities' => [['کرمانشاه', 'kermanshah-city'], ['اسلام‌آباد غرب', 'eslamabad-e-gharb'], ['جوانرود', 'javanrud'], ['کنگاور', 'kangavar'], ['سرپل ذهاب', 'sarpol-e-zahab'], ['سنقر', 'sonqor'], ['هرسین', 'harsin'], ['صحنه', 'sahneh'], ['پاوه', 'paveh'], ['گیلانغرب', 'gilangharb']]],
            ['name' => 'قزوین', 'slug' => 'qazvin', 'cities' => [['قزوین', 'qazvin-city'], ['الوند', 'alvand'], ['محمدیه', 'mohammadiyeh'], ['تاکستان', 'takestan'], ['آبیک', 'abyek'], ['بوئین‌زهرا', 'buin-zahra'], ['اقبالیه', 'eqbaliyeh'], ['محمودآباد نمونه', 'mahmudabad-nemooneh'], ['بیدستان', 'bidestan'], ['شال', 'shal']]],
            ['name' => 'همدان', 'slug' => 'hamedan', 'cities' => [['همدان', 'hamedan-city'], ['ملایر', 'malayer'], ['نهاوند', 'nahavand'], ['اسدآباد', 'asadabad'], ['تویسرکان', 'tuyserkan'], ['بهار', 'bahar'], ['کبودرآهنگ', 'kabudarahang'], ['لالجین', 'lalejin'], ['رزن', 'razan'], ['فامنین', 'famenin']]],
            ['name' => 'مرکزی', 'slug' => 'markazi', 'cities' => [['اراک', 'arak'], ['ساوه', 'saveh'], ['خمین', 'khomeyn'], ['محلات', 'mahallat'], ['دلیجان', 'delijan'], ['شازند', 'shazand'], ['مهاجران', 'mohajeran'], ['زرندیه', 'zarandieh'], ['تفرش', 'tafresh'], ['آشتیان', 'ashtian']]],
            ['name' => 'لرستان', 'slug' => 'lorestan', 'cities' => [['خرم‌آباد', 'khorramabad'], ['بروجرد', 'borujerd'], ['دورود', 'dorud'], ['کوهدشت', 'kuhdasht'], ['الیگودرز', 'aligudarz'], ['نورآباد', 'nurabad'], ['ازنا', 'azna'], ['الشتر', 'aleshtar'], ['پلدختر', 'poldokhtar'], ['سراب‌دوره', 'sarab-e-dowreh']]],
            ['name' => 'هرمزگان', 'slug' => 'hormozgan', 'cities' => [['بندرعباس', 'bandar-abbas'], ['میناب', 'minab'], ['قشم', 'qeshm'], ['کیش', 'kish'], ['بندرلنگه', 'bandar-lengeh'], ['رودان', 'rudan'], ['حاجی‌آباد', 'hajiabad'], ['کنگ', 'kong'], ['پارسیان', 'parsian'], ['جاسک', 'jask']]],
            ['name' => 'سیستان و بلوچستان', 'slug' => 'sistan-va-baluchestan', 'cities' => [['زاهدان', 'zahedan'], ['زابل', 'zabol'], ['ایرانشهر', 'iranshahr'], ['چابهار', 'chabahar'], ['سراوان', 'saravan'], ['خاش', 'khash'], ['کنارک', 'konarak'], ['جالق', 'jaleq'], ['نیک‌شهر', 'nikshahr'], ['پیشین', 'pishin']]],
            ['name' => 'کردستان', 'slug' => 'kurdistan', 'cities' => [['سنندج', 'sanandaj'], ['سقز', 'saqqez'], ['مریوان', 'marivan'], ['بانه', 'baneh'], ['قروه', 'qorveh'], ['کامیاران', 'kamyaran'], ['بیجار', 'bijar'], ['دیواندره', 'divandarreh'], ['دهگلان', 'dehgalan'], ['کانی‌سور', 'kani-sur']]],
            ['name' => 'گلستان', 'slug' => 'golestan', 'cities' => [['گرگان', 'gorgan'], ['گنبد کاووس', 'gonbad-e-kavus'], ['علی‌آباد کتول', 'aliabad-e-katul'], ['بندر ترکمن', 'bandar-torkaman'], ['کلاله', 'kalaleh'], ['آزادشهر', 'azadshahr'], ['کردکوی', 'kordkuy'], ['آق‌قلا', 'aq-qala'], ['مینودشت', 'minudasht'], ['گالیکش', 'galikesh']]],
            ['name' => 'سمنان', 'slug' => 'semnan', 'cities' => [['سمنان', 'semnan-city'], ['شاهرود', 'shahrud'], ['دامغان', 'damghan'], ['گرمسار', 'garmsar'], ['مهدی‌شهر', 'mehdishahr'], ['ایوانکی', 'eyvanki'], ['سرخه', 'sorkheh'], ['شهمیرزاد', 'shahmirzad'], ['بسطام', 'bastam'], ['آرادان', 'aradan']]],
            ['name' => 'زنجان', 'slug' => 'zanjan', 'cities' => [['زنجان', 'zanjan-city'], ['ابهر', 'abhar'], ['خرمدره', 'khorramdarreh'], ['قیدار', 'qeydar'], ['هیدج', 'hidaj'], ['صائین‌قلعه', 'saein-qaleh'], ['آب‌بر', 'ab-bar'], ['سلطانیه', 'soltaniyeh'], ['ماهنشان', 'mahneshan'], ['زرین‌آباد', 'zarrinabad']]],
            ['name' => 'اردبیل', 'slug' => 'ardabil', 'cities' => [['اردبیل', 'ardabil-city'], ['پارس‌آباد', 'parsabad'], ['مشگین‌شهر', 'meshginshahr'], ['خلخال', 'khalkhal'], ['گرمی', 'germi'], ['بیله‌سوار', 'bileh-sowar'], ['نمین', 'namin'], ['جعفرآباد', 'jafarabad'], ['گیوی', 'givi'], ['سرعین', 'sareyn']]],
            ['name' => 'بوشهر', 'slug' => 'bushehr', 'cities' => [['بوشهر', 'bushehr-city'], ['برازجان', 'borazjan'], ['بندر گناوه', 'bandar-genaveh'], ['بندر کنگان', 'bandar-kangan'], ['خورموج', 'khormuj'], ['جم', 'jam'], ['عسلویه', 'asaluyeh'], ['بندر دیلم', 'bandar-deylam'], ['اهرم', 'ahram'], ['بندر دیر', 'bandar-dayyer']]],
            ['name' => 'قم', 'slug' => 'qom', 'cities' => [['قم', 'qom-city'], ['قنوات', 'qanavat'], ['جعفریه', 'jafariyeh'], ['کهک', 'kahak'], ['دستجرد', 'dastjerd'], ['سلفچگان', 'salafchegan'], ['پردیسان', 'pardisan'], ['خلجستان', 'khalajestan'], ['قاهان', 'qahan'], ['فردو', 'fordo']]],
            ['name' => 'چهارمحال و بختیاری', 'slug' => 'chaharmahal-bakhtiari', 'cities' => [['شهرکرد', 'shahr-e-kord'], ['بروجن', 'borujen'], ['لردگان', 'lordegan'], ['فرخ‌شهر', 'farrokhshahr'], ['فارسان', 'farsan'], ['هفشجان', 'hafshejan'], ['جونقان', 'juneqan'], ['سامان', 'saman'], ['بن', 'ben'], ['سورشجان', 'sureshjan']]],
            ['name' => 'خراسان جنوبی', 'slug' => 'khorasan-jonubi', 'cities' => [['بیرجند', 'birjand'], ['قائن', 'qayen'], ['فردوس', 'ferdows'], ['طبس', 'tabas'], ['نهبندان', 'nehbandan'], ['سرایان', 'sarayan'], ['سربیشه', 'sarbisheh'], ['بشرویه', 'boshruyeh'], ['خوسف', 'khusf'], ['اسدیه', 'asadiyeh']]],
            ['name' => 'خراسان شمالی', 'slug' => 'khorasan-shomali', 'cities' => [['بجنورد', 'bojnurd'], ['شیروان', 'shirvan'], ['اسفراین', 'esfarayen'], ['آشخانه', 'ashkhaneh'], ['جاجرم', 'jajarm'], ['فاروج', 'faruj'], ['گرمه', 'garmeh'], ['راز', 'raz'], ['درق', 'daraq'], ['ایور', 'ivar']]],
            ['name' => 'ایلام', 'slug' => 'ilam', 'cities' => [['ایلام', 'ilam-city'], ['دهلران', 'dehloran'], ['ایوان', 'eyvan'], ['آبدانان', 'abdanan'], ['دره‌شهر', 'darreh-shahr'], ['مهران', 'mehran'], ['سرابله', 'sారableh'], ['ارکواز', 'arkavaz'], ['آسمان‌آباد', 'asmanabad'], ['چوار', 'chavar']]],
            ['name' => 'کهگیلویه و بویراحمد', 'slug' => 'kohgiluyeh-boyerahmad', 'cities' => [['یاسوج', 'yasuj'], ['دوگنبدان', 'dogonbadan'], ['دهدشت', 'dehdhasht'], ['لیکک', 'likak'], ['چرام', 'charam'], ['لنده', 'landeh'], ['باشت', 'basht'], ['سی‌سخت', 'sisakht'], ['سوق', 'suq'], ['دیشموک', 'dishmok']]],
        ];

        DB::transaction(function () use ($data): void {
            foreach ($data as $provinceData) {
                $province = Location::updateOrCreate(
                    ['slug' => $provinceData['slug']],
                    ['name' => $provinceData['name'], 'parent_id' => null],
                );

                foreach ($provinceData['cities'] as [$name, $slug]) {
                    $slug = $name === 'سرابله' ? 'sarbaleh' : $slug;

                    Location::updateOrCreate(
                        ['parent_id' => $province->id, 'slug' => $slug],
                        ['name' => $name],
                    );
                }
            }
        });

        Log::info('Iran locations seeded', [
            'function' => __METHOD__,
            'user_id' => null,
            'payload' => ['province_count' => count($data), 'city_count' => count($data) * 10],
            'trace' => null,
        ]);
    }
}
