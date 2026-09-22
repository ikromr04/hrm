import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface PositionItem {
    id: number;
    name: string;
    users_count: number;
}

/** Positions, shown in the UI as "Должность". */
export default function Positions({ items }: { items: PositionItem[] }) {
    return (
        <DirectoriesLayout title="Должности">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.name, users_count: item.users_count }))}
                field="name"
                route="directories.positions"
                labels={{ add: 'Добавить должность', create: 'Новая должность', edit: 'Изменить должность', accusative: 'должность' }}
                employeesUrl={(item) => route('employees.index', { position: [item.id] })}
            />
        </DirectoriesLayout>
    );
}
