<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Лолита',
                'surname' => 'Холматова',
                'patronymic' => 'Нажмутдиновна',
                'login' => 'manager',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шухрат',
                'surname' => 'Сафаров',
                'patronymic' => 'Шарофудинович',
                'login' => 'admin',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нозимчон',
                'surname' => 'Хакимов',
                'patronymic' => 'Юнусович',
                'login' => 'user',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Киличбеков',
                'patronymic' => 'Киличбекович',
                'login' => 'firdavs_kilichbekov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Азимчон',
                'surname' => 'Вохиди',
                'patronymic' => null,
                'login' => 'azimjon_vohidi',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Парвиз',
                'surname' => 'Иброхимбеков',
                'patronymic' => 'Джаборович',
                'login' => 'parviz_ibrohimbekov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Муллохасан',
                'surname' => 'Тураев',
                'patronymic' => 'Мирзоевич',
                'login' => 'mullohasan_turaev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Анвар',
                'surname' => 'Джаборов',
                'patronymic' => 'Закирович',
                'login' => 'anvar_jabarov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Джоми',
                'surname' => 'Султонзода',
                'patronymic' => null,
                'login' => 'jomi_sultonzoda',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Иноятшо',
                'surname' => 'Насридиншоев',
                'patronymic' => 'Мирджонович',
                'login' => 'inoyatsho_nasridinshoev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Парвина',
                'surname' => 'Мирахмедова',
                'patronymic' => 'Кулиевна',
                'login' => 'parvina_mirahmedova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Сухроб',
                'surname' => 'Мирзоев',
                'patronymic' => 'Комилчонович',
                'login' => 'mirzoev_suhrob',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Сироджов',
                'patronymic' => 'Махмадуллоевич',
                'login' => 'fidavs_sirojov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фаррух',
                'surname' => 'Каюмов',
                'patronymic' => 'Саъдуллоевич',
                'login' => 'farruh_kaumov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фариз',
                'surname' => 'Мирзоев',
                'patronymic' => 'Шералиевич',
                'login' => 'fariz_mirzoev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бехрузи',
                'surname' => 'Музаффар',
                'patronymic' => null,
                'login' => 'behruzi_muzaffar',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Мехроч',
                'surname' => 'Хакимов',
                'patronymic' => 'Зуфарович',
                'login' => 'mehroj_hakimov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Акмал',
                'surname' => 'Шербадалов',
                'patronymic' => 'Махмадуллоевич',
                'login' => 'akmal_sherbadalov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Азиз',
                'surname' => 'Носиров',
                'patronymic' => 'Дилшодович',
                'login' => 'aziz_nosirov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Азим',
                'surname' => 'Нажмуддинов',
                'patronymic' => 'Акбарович',
                'login' => 'azim_najmuddinov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бободжон',
                'surname' => 'Шарбатов',
                'patronymic' => 'Фатохович',
                'login' => 'bobojon_sharbatov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нодира',
                'surname' => 'Иноятова',
                'patronymic' => 'Октябревна',
                'login' => 'nodira_inoyatova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Комила',
                'surname' => 'Азимова',
                'patronymic' => 'Джамшедовна',
                'login' => 'komila_azimova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Зайнаб',
                'surname' => 'Сунатова',
                'patronymic' => 'Назриевна',
                'login' => 'zainab_sunatova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Далер',
                'surname' => 'Кодиров',
                'patronymic' => 'Хайдарович',
                'login' => 'daler_kodirov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Аниса',
                'surname' => 'Мансурова',
                'patronymic' => 'Мумтозовна',
                'login' => 'anisa_mansurova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Марьям',
                'surname' => 'Зарипова',
                'patronymic' => 'Рустамчоновна',
                'login' => 'maryam_zaripova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Зебо',
                'surname' => 'Каримова',
                'patronymic' => 'Махмадуллоевна',
                'login' => 'zebo_karimova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Алим',
                'surname' => 'Мунаваров',
                'patronymic' => 'Сангмамадович',
                'login' => 'alim_munavarov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Олимджон',
                'surname' => 'Нурулоев',
                'patronymic' => 'Сангинмуродович',
                'login' => 'olimjon_nuruloev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шахриёр',
                'surname' => 'Пиров',
                'patronymic' => 'Шарофович',
                'login' => 'shahrier_pirov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Абдукарим',
                'surname' => 'Каримов',
                'patronymic' => 'Абдухалимович',
                'login' => 'abdukarim_karimov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бехруз',
                'surname' => 'Холов',
                'patronymic' => 'Нуралиевич',
                'login' => 'behruz_holov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Манзура',
                'surname' => 'Расулова',
                'patronymic' => 'Мукимджоновна',
                'login' => 'manzura_rasulova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Хумайро',
                'surname' => 'Махмадалиева',
                'patronymic' => 'Курбоналиевна',
                'login' => 'humairo_mahmadalievna',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нурия',
                'surname' => 'Маджидова',
                'patronymic' => 'Рахматуллоевна',
                'login' => 'nuriya_majidova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Эхсон',
                'surname' => 'Алифшоев',
                'patronymic' => 'Олимшоевич',
                'login' => 'ehson_alifshoev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Садбарг',
                'surname' => 'Валиева',
                'patronymic' => 'Джумахоновна',
                'login' => 'sadbarg_valieva',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Кудратулло',
                'surname' => 'Ашуров',
                'patronymic' => 'Мансуруллоевич',
                'login' => 'ashurov_kudratullo',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Умеджони',
                'surname' => 'Неъмон',
                'patronymic' => 'Мансуруллоевич',
                'login' => 'umedjoni_nemon',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Омина',
                'surname' => 'Шомирзоева',
                'patronymic' => 'Исмоиловна',
                'login' => 'omina_shomirzoeva',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Акрам',
                'surname' => 'Толибов',
                'patronymic' => 'Сайфуллоевич',
                'login' => 'akram_tolibov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Баходур',
                'surname' => 'Нарзуллоев',
                'patronymic' => 'Хусенович',
                'login' => 'bahodur_narzulloevich',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бобурхон',
                'surname' => 'Нуридинов',
                'patronymic' => 'Холматович',
                'login' => 'boburhon_nuridinov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Икром',
                'surname' => 'Рахимов',
                'patronymic' => 'Дилшодович',
                'login' => 'ikrom_rahimov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Денис',
                'surname' => 'Ульянов',
                'patronymic' => 'Владимирович',
                'login' => 'denis_uliyanov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Анна',
                'surname' => 'Мирзоева',
                'patronymic' => 'Юрьевна',
                'login' => 'anna_mirzoeva',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Лола',
                'surname' => 'Азимова',
                'patronymic' => 'Джамшедовна',
                'login' => 'lola_azimova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шерзод',
                'surname' => 'Холматов',
                'patronymic' => 'Тимурович',
                'login' => 'sherzod_holmatov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Артиков',
                'patronymic' => 'Мухаммадалиевич',
                'login' => 'firdavs_artikov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Комрон',
                'surname' => 'Имомалиев',
                'patronymic' => 'Хуршедович',
                'login' => 'komron_imomaliev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Таманно',
                'surname' => 'Чумаева',
                'patronymic' => 'Аличоновна',
                'login' => 'tamanno_jumaeva',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Миробиддин',
                'surname' => 'Мирзоев',
                'patronymic' => 'Сабохиддинович',
                'login' => 'mirobiddin_mirzoev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шамсиддин',
                'surname' => 'Мирзоев',
                'patronymic' => 'Ниёзмахмадович',
                'login' => 'shamsiddin_mirzoev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Джамол',
                'surname' => 'Джамолханов',
                'patronymic' => 'Эхсонджонович',
                'login' => 'jamol_jamolhanov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Сабрина',
                'surname' => 'Кадирова',
                'patronymic' => 'Джамшедовна',
                'login' => 'sabrina_kadirova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нурмухаммад',
                'surname' => 'Забиров',
                'patronymic' => 'Собирджонович',
                'login' => 'nurmuhammad_zabirov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Амир',
                'surname' => 'Арбобов',
                'patronymic' => 'Алишерович',
                'login' => 'amir_arbobov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Мадина',
                'surname' => 'Зульфикорбекова',
                'patronymic' => 'Бахтиёрова',
                'login' => 'madina_zulfikorbekova',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Мухаммадулло',
                'surname' => 'Рахматуллоев',
                'patronymic' => 'Нарзуллоевич',
                'login' => 'muhammadullo_rahmatulloev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Анора',
                'surname' => 'Усмонзода',
                'patronymic' => 'Джамшед',
                'login' => 'anora_usmonzoda',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Муродчон',
                'surname' => 'Бахромов',
                'patronymic' => 'Шухратович',
                'login' => 'murodjon_bahromov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фарходжон',
                'surname' => 'Одилжонов',
                'patronymic' => 'Исмоилжонович',
                'login' => 'farhod_odiljonov',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Пайрав',
                'surname' => 'Исоев',
                'patronymic' => 'Умеджонович',
                'login' => 'payrav_isoev',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ]
        ];

        $password = Hash::make('password');

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'surname' => $user['surname'],
                'patronymic' => $user['patronymic'],
                'login' => $user['login'],
                'avatar' => $user['avatar'],
                'avatar_thumb' => $user['avatar_thumb'],
                'password' => $password
            ]);
        }
    }
}
