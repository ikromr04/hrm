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
                'login' => 'lolita_holmatova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f831821f1df.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f8318220cef.jpg'
            ],
            [
                'name' => 'Шухрат',
                'surname' => 'Сафаров',
                'patronymic' => 'Шарофудинович',
                'login' => 'ikulobm',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff79e264bc.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff79e277da.jpg'
            ],
            [
                'name' => 'Нозимчон',
                'surname' => 'Хакимов',
                'patronymic' => 'Юнусович',
                'login' => 'hakimov_nozim',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff84167114.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff84168427.jpg'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Киличбеков',
                'patronymic' => 'Киличбекович',
                'login' => 'firdavs_kilichbekov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28b5f7f6d0.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28b5f80a75.jpg'
            ],
            [
                'name' => 'Азимчон',
                'surname' => 'Вохиди',
                'patronymic' => null,
                'login' => 'azimjon_vohidi',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0192f30af2.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0192f31e38.jpg'
            ],
            [
                'name' => 'Парвиз',
                'surname' => 'Иброхимбеков',
                'patronymic' => 'Джаборович',
                'login' => 'parviz_ibrohimbekov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0199a30d8e.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0199a320ab.jpg'
            ],
            [
                'name' => 'Муллохасан',
                'surname' => 'Тураев',
                'patronymic' => 'Мирзоевич',
                'login' => 'mullohasan_turaev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f01a7aa670b.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f01a7aa7670.jpg'
            ],
            [
                'name' => 'Анвар',
                'surname' => 'Джаборов',
                'patronymic' => 'Закирович',
                'login' => 'anvar_jabarov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f01d6b87d61.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f01d6b890c8.jpg'
            ],
            [
                'name' => 'Джоми',
                'surname' => 'Султонзода',
                'patronymic' => null,
                'login' => 'jomi_sultonzoda',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f01e75c3efd.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f01e75c52d7.jpg'
            ],
            [
                'name' => 'Иноятшо',
                'surname' => 'Насридиншоев',
                'patronymic' => 'Мирджонович',
                'login' => 'inoyatsho_nasridinshoev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f01f76afbae.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f01f76b0b6d.jpg'
            ],
            [
                'name' => 'Парвина',
                'surname' => 'Мирахмедова',
                'patronymic' => 'Кулиевна',
                'login' => 'parvina_mirahmedova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f025bfd3f42.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f025bfd517f.jpg'
            ],
            [
                'name' => 'Сухроб',
                'surname' => 'Мирзоев',
                'patronymic' => 'Комилчонович',
                'login' => 'mirzoev_suhrob',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f028495aa8f.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f028495c25b.jpg'
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Сироджов',
                'patronymic' => 'Махмадуллоевич',
                'login' => 'fidavs_sirojov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f02d687d4d3.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f02d687e7b3.jpg'
            ],
            [
                'name' => 'Фаррух',
                'surname' => 'Каюмов',
                'patronymic' => 'Саъдуллоевич',
                'login' => 'farruh_kaumov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f288190d4d3.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f288190ec69.jpg'
            ],
            [
                'name' => 'Фариз',
                'surname' => 'Мирзоев',
                'patronymic' => 'Шералиевич',
                'login' => 'fariz_mirzoev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f289475f0b9.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f289476041e.jpg'
            ],
            [
                'name' => 'Бехрузи',
                'surname' => 'Музаффар',
                'patronymic' => null,
                'login' => 'behruzi_muzaffar',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f2899dd492f.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f2899dd5c8d.jpg'
            ],
            [
                'name' => 'Мехроч',
                'surname' => 'Хакимов',
                'patronymic' => 'Зуфарович',
                'login' => 'mehroj_hakimov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28a0457e38.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28a0459174.jpg'
            ],
            [
                'name' => 'Акмал',
                'surname' => 'Шербадалов',
                'patronymic' => 'Махмадуллоевич',
                'login' => 'akmal_sherbadalov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28aee168af.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28aee17d9c.jpg'
            ],
            [
                'name' => 'Азиз',
                'surname' => 'Носиров',
                'patronymic' => 'Дилшодович',
                'login' => 'aziz_nosirov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28cf99bfbd.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28cf99d2f6.jpg'
            ],
            [
                'name' => 'Азим',
                'surname' => 'Нажмуддинов',
                'patronymic' => 'Акбарович',
                'login' => 'azim_najmuddinov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28d2604cc8.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28d2605fd6.jpg'
            ],
            [
                'name' => 'Бободжон',
                'surname' => 'Шарбатов',
                'patronymic' => 'Фатохович',
                'login' => 'bobojon_sharbatov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28faeaf565.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28faeb08ed.jpg'
            ],
            [
                'name' => 'Нодира',
                'surname' => 'Иноятова',
                'patronymic' => 'Октябревна',
                'login' => 'nodira_inoyatova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f27fc963e4e.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f27fc966240.jpg'
            ],
            [
                'name' => 'Комила',
                'surname' => 'Азимова',
                'patronymic' => 'Джамшедовна',
                'login' => 'komila_azimova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efdf6b0b366.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efdf6b0d4c8.jpg'
            ],
            [
                'name' => 'Зайнаб',
                'surname' => 'Сунатова',
                'patronymic' => 'Назриевна',
                'login' => 'zainab_sunatova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f2809ba0d43.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f2809ba1f60.jpg'
            ],
            [
                'name' => 'Далер',
                'surname' => 'Кодиров',
                'patronymic' => 'Хайдарович',
                'login' => 'daler_kodirov',
                'avatar' => null,
                'avatar_thumb' => null
            ],
            [
                'name' => 'Аниса',
                'surname' => 'Мансурова',
                'patronymic' => 'Мумтозовна',
                'login' => 'anisa_mansurova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f2813e2d2c5.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f2813e2e1a8.jpg'
            ],
            [
                'name' => 'Марьям',
                'surname' => 'Зарипова',
                'patronymic' => 'Рустамчоновна',
                'login' => 'maryam_zaripova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f02f7b3f391.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f02f7b406b5.jpg'
            ],
            [
                'name' => 'Зебо',
                'surname' => 'Каримова',
                'patronymic' => 'Махмадуллоевна',
                'login' => 'zebo_karimova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0302e92917.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0302e9406c.jpg'
            ],
            [
                'name' => 'Алим',
                'surname' => 'Мунаваров',
                'patronymic' => 'Сангмамадович',
                'login' => 'alim_munavarov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0305c5dfea.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0305c5f728.jpg'
            ],
            [
                'name' => 'Олимджон',
                'surname' => 'Нурулоев',
                'patronymic' => 'Сангинмуродович',
                'login' => 'olimjon_nuruloev',
                'avatar' => null,
                'avatar_thumb' => null
            ],
            [
                'name' => 'Шахриёр',
                'surname' => 'Пиров',
                'patronymic' => 'Шарофович',
                'login' => 'shahrier_pirov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f281edc343f.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f281edc423f.jpg'
            ],
            [
                'name' => 'Абдукарим',
                'surname' => 'Каримов',
                'patronymic' => 'Абдухалимович',
                'login' => 'abdukarim_karimov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f2820d95663.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f2820d96a4f.jpg'
            ],
            [
                'name' => 'Бехруз',
                'surname' => 'Холов',
                'patronymic' => 'Нуралиевич',
                'login' => 'behruz_holov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f2823c2910b.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f2823c2a4df.jpg'
            ],
            [
                'name' => 'Манзура',
                'surname' => 'Расулова',
                'patronymic' => 'Мукимджоновна',
                'login' => 'manzura_rasulova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f28341460a9.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f28341473fa.jpg'
            ],
            [
                'name' => 'Хумайро',
                'surname' => 'Махмадалиева',
                'patronymic' => 'Курбоналиевна',
                'login' => 'humairo_mahmadalievna',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f03b2058429.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f03b20597b4.jpg'
            ],
            [
                'name' => 'Нурия',
                'surname' => 'Маджидова',
                'patronymic' => 'Рахматуллоевна',
                'login' => 'nuriya_majidova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0339d30a2f.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0339d31d16.jpg'
            ],
            [
                'name' => 'Эхсон',
                'surname' => 'Алифшоев',
                'patronymic' => 'Олимшоевич',
                'login' => 'ehson_alifshoev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff8cf20fb2.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff8cf22256.jpg'
            ],
            [
                'name' => 'Садбарг',
                'surname' => 'Валиева',
                'patronymic' => 'Джумахоновна',
                'login' => 'sadbarg_valieva',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff90c4e732.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff90c4fac1.jpg'
            ],
            [
                'name' => 'Кудратулло',
                'surname' => 'Ашуров',
                'patronymic' => 'Мансуруллоевич',
                'login' => 'ashurov_kudratullo',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff9c0ad6bc.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff9c0aeaaa.jpg'
            ],
            [
                'name' => 'Умеджони',
                'surname' => 'Неъмон',
                'patronymic' => 'Мансуруллоевич',
                'login' => 'umedjoni_nemon',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65effa63091a0.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65effa630a0c7.jpg'
            ],
            [
                'name' => 'Омина',
                'surname' => 'Шомирзоева',
                'patronymic' => 'Исмоиловна',
                'login' => 'omina_shomirzoeva',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65effafcdf548.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65effafce0871.jpg'
            ],
            [
                'name' => 'Акрам',
                'surname' => 'Толибов',
                'patronymic' => 'Сайфуллоевич',
                'login' => 'akram_tolibov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65effbee0345b.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65effbee04859.jpg'
            ],
            [
                'name' => 'Баходур',
                'surname' => 'Нарзуллоев',
                'patronymic' => 'Хусенович',
                'login' => 'bahodur_narzulloevich',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65effcb56ace7.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65effcb56c02f.jpg'
            ],
            [
                'name' => 'Бобурхон',
                'surname' => 'Нуридинов',
                'patronymic' => 'Холматович',
                'login' => 'boburhon_nuridinov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0016cf3bb0.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0016d00d0a.jpg'
            ],
            [
                'name' => 'Икром',
                'surname' => 'Рахимов',
                'patronymic' => 'Дилшодович',
                'login' => 'ikrom_rahimov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f001b9f3586.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f001ba00670.jpg'
            ],
            [
                'name' => 'Денис',
                'surname' => 'Ульянов',
                'patronymic' => 'Владимирович',
                'login' => 'denis_uliyanov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f0141283b04.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f0141284e53.jpg'
            ],
            [
                'name' => 'Анна',
                'surname' => 'Мирзоева',
                'patronymic' => 'Юрьевна',
                'login' => 'anna_mirzoeva',
                'avatar' => null,
                'avatar_thumb' => null
            ],
            [
                'name' => 'Лола',
                'surname' => 'Азимова',
                'patronymic' => 'Джамшедовна',
                'login' => 'lola_azimova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efe720bff5d.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efe720c1348.jpg'
            ],
            [
                'name' => 'Шерзод',
                'surname' => 'Холматов',
                'patronymic' => 'Тимурович',
                'login' => 'sherzod_holmatov',
                'avatar' => null,
                'avatar_thumb' => null
            ],
            [
                'name' => 'Фирдавс',
                'surname' => 'Артиков',
                'patronymic' => 'Мухаммадалиевич',
                'login' => 'firdavs_artikov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efe75b49751.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efe75b4a9fd.jpg'
            ],
            [
                'name' => 'Комрон',
                'surname' => 'Имомалиев',
                'patronymic' => 'Хуршедович',
                'login' => 'komron_imomaliev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efeb40d3c6d.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efeb40d508e.jpg'
            ],
            [
                'name' => 'Таманно',
                'surname' => 'Чумаева',
                'patronymic' => 'Аличоновна',
                'login' => 'tamanno_jumaeva',
                'avatar' => null,
                'avatar_thumb' => null
            ],
            [
                'name' => 'Миробиддин',
                'surname' => 'Мирзоев',
                'patronymic' => 'Сабохиддинович',
                'login' => 'mirobiddin_mirzoev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efebafb963c.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efebafbacb0.jpg'
            ],
            [
                'name' => 'Шамсиддин',
                'surname' => 'Мирзоев',
                'patronymic' => 'Ниёзмахмадович',
                'login' => 'shamsiddin_mirzoev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efec3940674.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efec3941a30.jpg'
            ],
            [
                'name' => 'Джамол',
                'surname' => 'Джамолханов',
                'patronymic' => 'Эхсонджонович',
                'login' => 'jamol_jamolhanov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efec7c25143.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efec7c264ad.jpg'
            ],
            [
                'name' => 'Сабрина',
                'surname' => 'Кадирова',
                'patronymic' => 'Джамшедовна',
                'login' => 'sabrina_kadirova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efedf365527.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efedf36683e.jpg'
            ],
            [
                'name' => 'Нурмухаммад',
                'surname' => 'Забиров',
                'patronymic' => 'Собирджонович',
                'login' => 'nurmuhammad_zabirov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efef488e7e3.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efef488fb06.jpg'
            ],
            [
                'name' => 'Амир',
                'surname' => 'Арбобов',
                'patronymic' => 'Алишерович',
                'login' => 'amir_arbobov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65efefd4c1c15.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65efefd4c2fa3.jpg'
            ],
            [
                'name' => 'Мадина',
                'surname' => 'Зульфикорбекова',
                'patronymic' => 'Бахтиёрова',
                'login' => 'madina_zulfikorbekova',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff233070c9.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff233084ab.jpg'
            ],
            [
                'name' => 'Мухаммадулло',
                'surname' => 'Рахматуллоев',
                'patronymic' => 'Нарзуллоевич',
                'login' => 'muhammadullo_rahmatulloev',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff26d4742d.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff26d48731.jpg'
            ],
            [
                'name' => 'Анора',
                'surname' => 'Усмонзода',
                'patronymic' => 'Джамшед',
                'login' => 'anora_usmonzoda',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff3df2005f.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff3df213d5.jpg'
            ],
            [
                'name' => 'Муродчон',
                'surname' => 'Бахромов',
                'patronymic' => 'Шухратович',
                'login' => 'murodjon_bahromov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65eff51f6eadf.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65eff51f6fd7d.jpg'
            ],
            [
                'name' => 'Фарходжон',
                'surname' => 'Одилжонов',
                'patronymic' => 'Исмоилжонович',
                'login' => 'farhod_odiljonov',
                'avatar' => 'https://hr.evolet.tj/uploads/img/employees/65f291c5416c8.jpg',
                'avatar_thumb' => 'https://hr.evolet.tj/uploads/img/employees/thumbs/65f291c542a58.jpg'
            ],
            [
                'name' => 'Пайрав',
                'surname' => 'Исоев',
                'patronymic' => 'Умеджонович',
                'login' => 'payrav_isoev',
                'avatar' => null,
                'avatar_thumb' => null
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
