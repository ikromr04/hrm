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
                'email' => 'manager@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шухрат',
                'surname' => 'Сафаров',
                'patronymic' => 'Шарофудинович',
                'email' => 'admin@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нозимчон',
                'surname' => 'Хакимов',
                'patronymic' => 'Юнусович',
                'email' => 'user@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Киличбеков',
                'patronymic' => 'Киличбекович',
                'email' => 'firdavs_kilichbekov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Азимчон',
                'surname' => 'Вохиди',
                'patronymic' => null,
                'email' => 'azimjon_vohidi@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Парвиз',
                'surname' => 'Иброхимбеков',
                'patronymic' => 'Джаборович',
                'email' => 'parviz_ibrohimbekov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Муллохасан',
                'surname' => 'Тураев',
                'patronymic' => 'Мирзоевич',
                'email' => 'mullohasan_turaev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Анвар',
                'surname' => 'Джаборов',
                'patronymic' => 'Закирович',
                'email' => 'anvar_jabarov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Джоми',
                'surname' => 'Султонзода',
                'patronymic' => null,
                'email' => 'jomi_sultonzoda@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Иноятшо',
                'surname' => 'Насридиншоев',
                'patronymic' => 'Мирджонович',
                'email' => 'inoyatsho_nasridinshoev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Парвина',
                'surname' => 'Мирахмедова',
                'patronymic' => 'Кулиевна',
                'email' => 'parvina_mirahmedova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Сухроб',
                'surname' => 'Мирзоев',
                'patronymic' => 'Комилчонович',
                'email' => 'mirzoev_suhrob@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Сироджов',
                'patronymic' => 'Махмадуллоевич',
                'email' => 'fidavs_sirojov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фаррух',
                'surname' => 'Каюмов',
                'patronymic' => 'Саъдуллоевич',
                'email' => 'farruh_kaumov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фариз',
                'surname' => 'Мирзоев',
                'patronymic' => 'Шералиевич',
                'email' => 'fariz_mirzoev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бехрузи',
                'surname' => 'Музаффар',
                'patronymic' => null,
                'email' => 'behruzi_muzaffar@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Мехроч',
                'surname' => 'Хакимов',
                'patronymic' => 'Зуфарович',
                'email' => 'mehroj_hakimov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Акмал',
                'surname' => 'Шербадалов',
                'patronymic' => 'Махмадуллоевич',
                'email' => 'akmal_sherbadalov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Азиз',
                'surname' => 'Носиров',
                'patronymic' => 'Дилшодович',
                'email' => 'aziz_nosirov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Азим',
                'surname' => 'Нажмуддинов',
                'patronymic' => 'Акбарович',
                'email' => 'azim_najmuddinov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бободжон',
                'surname' => 'Шарбатов',
                'patronymic' => 'Фатохович',
                'email' => 'bobojon_sharbatov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нодира',
                'surname' => 'Иноятова',
                'patronymic' => 'Октябревна',
                'email' => 'nodira_inoyatova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Комила',
                'surname' => 'Азимова',
                'patronymic' => 'Джамшедовна',
                'email' => 'komila_azimova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Зайнаб',
                'surname' => 'Сунатова',
                'patronymic' => 'Назриевна',
                'email' => 'zainab_sunatova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Далер',
                'surname' => 'Кодиров',
                'patronymic' => 'Хайдарович',
                'email' => 'daler_kodirov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Аниса',
                'surname' => 'Мансурова',
                'patronymic' => 'Мумтозовна',
                'email' => 'anisa_mansurova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Марьям',
                'surname' => 'Зарипова',
                'patronymic' => 'Рустамчоновна',
                'email' => 'maryam_zaripova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Зебо',
                'surname' => 'Каримова',
                'patronymic' => 'Махмадуллоевна',
                'email' => 'zebo_karimova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Алим',
                'surname' => 'Мунаваров',
                'patronymic' => 'Сангмамадович',
                'email' => 'alim_munavarov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Олимджон',
                'surname' => 'Нурулоев',
                'patronymic' => 'Сангинмуродович',
                'email' => 'olimjon_nuruloev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шахриёр',
                'surname' => 'Пиров',
                'patronymic' => 'Шарофович',
                'email' => 'shahrier_pirov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Абдукарим',
                'surname' => 'Каримов',
                'patronymic' => 'Абдухалимович',
                'email' => 'abdukarim_karimov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бехруз',
                'surname' => 'Холов',
                'patronymic' => 'Нуралиевич',
                'email' => 'behruz_holov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Манзура',
                'surname' => 'Расулова',
                'patronymic' => 'Мукимджоновна',
                'email' => 'manzura_rasulova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Хумайро',
                'surname' => 'Махмадалиева',
                'patronymic' => 'Курбоналиевна',
                'email' => 'humairo_mahmadalievna@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нурия',
                'surname' => 'Маджидова',
                'patronymic' => 'Рахматуллоевна',
                'email' => 'nuriya_majidova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Эхсон',
                'surname' => 'Алифшоев',
                'patronymic' => 'Олимшоевич',
                'email' => 'ehson_alifshoev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Садбарг',
                'surname' => 'Валиева',
                'patronymic' => 'Джумахоновна',
                'email' => 'sadbarg_valieva@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Кудратулло',
                'surname' => 'Ашуров',
                'patronymic' => 'Мансуруллоевич',
                'email' => 'ashurov_kudratullo@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Умеджони',
                'surname' => 'Неъмон',
                'patronymic' => 'Мансуруллоевич',
                'email' => 'umedjoni_nemon@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Омина',
                'surname' => 'Шомирзоева',
                'patronymic' => 'Исмоиловна',
                'email' => 'omina_shomirzoeva@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Акрам',
                'surname' => 'Толибов',
                'patronymic' => 'Сайфуллоевич',
                'email' => 'akram_tolibov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Баходур',
                'surname' => 'Нарзуллоев',
                'patronymic' => 'Хусенович',
                'email' => 'bahodur_narzulloevich@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Бобурхон',
                'surname' => 'Нуридинов',
                'patronymic' => 'Холматович',
                'email' => 'boburhon_nuridinov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Икром',
                'surname' => 'Рахимов',
                'patronymic' => 'Дилшодович',
                'email' => 'ikrom_rahimov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Денис',
                'surname' => 'Ульянов',
                'patronymic' => 'Владимирович',
                'email' => 'denis_uliyanov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Анна',
                'surname' => 'Мирзоева',
                'patronymic' => 'Юрьевна',
                'email' => 'anna_mirzoeva@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Лола',
                'surname' => 'Азимова',
                'patronymic' => 'Джамшедовна',
                'email' => 'lola_azimova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шерзод',
                'surname' => 'Холматов',
                'patronymic' => 'Тимурович',
                'email' => 'sherzod_holmatov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Артиков',
                'patronymic' => 'Мухаммадалиевич',
                'email' => 'firdavs_artikov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Комрон',
                'surname' => 'Имомалиев',
                'patronymic' => 'Хуршедович',
                'email' => 'komron_imomaliev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Таманно',
                'surname' => 'Чумаева',
                'patronymic' => 'Аличоновна',
                'email' => 'tamanno_jumaeva@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Миробиддин',
                'surname' => 'Мирзоев',
                'patronymic' => 'Сабохиддинович',
                'email' => 'mirobiddin_mirzoev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Шамсиддин',
                'surname' => 'Мирзоев',
                'patronymic' => 'Ниёзмахмадович',
                'email' => 'shamsiddin_mirzoev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Джамол',
                'surname' => 'Джамолханов',
                'patronymic' => 'Эхсонджонович',
                'email' => 'jamol_jamolhanov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Сабрина',
                'surname' => 'Кадирова',
                'patronymic' => 'Джамшедовна',
                'email' => 'sabrina_kadirova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Нурмухаммад',
                'surname' => 'Забиров',
                'patronymic' => 'Собирджонович',
                'email' => 'nurmuhammad_zabirov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Амир',
                'surname' => 'Арбобов',
                'patronymic' => 'Алишерович',
                'email' => 'amir_arbobov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Мадина',
                'surname' => 'Зульфикорбекова',
                'patronymic' => 'Бахтиёрова',
                'email' => 'madina_zulfikorbekova@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Мухаммадулло',
                'surname' => 'Рахматуллоев',
                'patronymic' => 'Нарзуллоевич',
                'email' => 'muhammadullo_rahmatulloev@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Анора',
                'surname' => 'Усмонзода',
                'patronymic' => 'Джамшед',
                'email' => 'anora_usmonzoda@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Муродчон',
                'surname' => 'Бахромов',
                'patronymic' => 'Шухратович',
                'email' => 'murodjon_bahromov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Фарходжон',
                'surname' => 'Одилжонов',
                'patronymic' => 'Исмоилжонович',
                'email' => 'farhod_odiljonov@gmail.com',
                'avatar' => 'https://picsum.photos/945/945',
                'avatar_thumb' => 'https://picsum.photos/144/144'
            ],
            [
                'name' => 'Пайрав',
                'surname' => 'Исоев',
                'patronymic' => 'Умеджонович',
                'email' => 'payrav_isoev@gmail.com',
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
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'avatar_thumb' => $user['avatar_thumb'],
                'password' => $password
            ]);
        }
    }
}
