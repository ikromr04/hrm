import { type CardFieldGroup } from '@/components/card-fields';
import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';
import { type AccessSection } from '@/lib/access';

interface RoleItem {
    id: number;
    name: string;
    title: string;
    users_count: number;
    protected: boolean;
    /** What the position opens, by right key. */
    permissions: string[];
}

/** Access roles, shown in the UI as "Позиция". */
export default function Roles({
    items,
    sections,
    fields,
    profileFields,
    defaults,
    canManageAccess,
}: {
    items: RoleItem[];
    sections: AccessSection[];
    /** The lines of an employee card, read and changed, chosen in the same dialog. */
    fields: CardFieldGroup[];
    /** The same lines as the rights to one's own card, asked separately. */
    profileFields: CardFieldGroup[];
    /** What a new position starts with, as the server defines it. */
    defaults: string[];
    canManageAccess: boolean;
}) {
    const byId = new Map(items.map((item) => [item.id, item]));

    return (
        <DirectoriesLayout title="Позиции">
            <DirectoryManager
                items={items.map((item) => ({
                    id: item.id,
                    label: item.title,
                    users_count: item.users_count,
                    protected: item.protected,
                    permissions: item.permissions,
                }))}
                field="title"
                route="directories.roles"
                labels={{ add: 'Добавить позицию', create: 'Новая позиция', edit: 'Изменить позицию', accusative: 'позицию' }}
                employeesUrl={(item) => route('employees.index', { role: [byId.get(item.id)!.name] })}
                // A position is no use until somebody says what it opens, so the
                // rights are ticked in the same dialog — for whoever may decide.
                rights={canManageAccess ? sections : undefined}
                cardFields={canManageAccess ? fields : undefined}
                profileFields={canManageAccess ? profileFields : undefined}
                defaultRights={defaults}
            />
        </DirectoriesLayout>
    );
}
