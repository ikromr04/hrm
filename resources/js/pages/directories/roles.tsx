import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface RoleItem {
    id: number;
    name: string;
    title: string;
    users_count: number;
    protected: boolean;
}

/** Access roles, shown in the UI as "Позиция". */
export default function Roles({ items }: { items: RoleItem[] }) {
    const byId = new Map(items.map((item) => [item.id, item]));

    return (
        <DirectoriesLayout title="Позиции">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.title, users_count: item.users_count, protected: item.protected }))}
                field="title"
                route="directories.roles"
                labels={{ add: 'Добавить позицию', create: 'Новая позиция', edit: 'Изменить позицию', accusative: 'позицию' }}
                employeesUrl={(item) => route('employees.index', { role: [byId.get(item.id)!.name] })}
            />
        </DirectoriesLayout>
    );
}
