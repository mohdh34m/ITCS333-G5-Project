export async function fetchGroups() {
    try {
        const response = await fetch('data/groups.json');
        if (!response.ok) {
            alert("Network response was not ok");
        }
        return await response.json();
    } catch (error) {
        console.error('Problem fetching Groups:', error);
    }
}
