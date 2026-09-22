import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface LanguageItem {
    id: number;
    name: string;
    users_count: number;
}

/** Languages employees speak, each with a level. */
export default function Languages({ items }: { items: LanguageItem[] }) {
    return (
        <DirectoriesLayout title="Языки">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.name, users_count: item.users_count }))}
                field="name"
                route="directories.languages"
                labels={{ add: 'Добавить язык', create: 'Новый язык', edit: 'Изменить язык', accusative: 'язык' }}
                employeesUrl={(item) => route('employees.index', { language: [item.id] })}
            />
        </DirectoriesLayout>
    );
}
