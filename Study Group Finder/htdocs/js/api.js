export async function fetchGroups(page = 1, limit = 8) {
    const res = await fetch(`https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/get_all_groups.php?page=${page}&limit=${limit}`);
    const data = await res.json()
    return data
}

export async function fetchDetailGroup(groupId) {
    const res = await fetch(`https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/get_group.php?id=${groupId}`);
    const data = await res.json();
    console.log(data)
    return data;
}


export async function fetchAllGroups() {
    const res = await fetch(`https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/all_groups.php`);
    const data = await res.json();
    console.log(data)
    return data;
}