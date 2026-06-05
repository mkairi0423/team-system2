function addFood() {

    const name =
        document.getElementById("foodName").value;

    const amount =
        document.getElementById("foodAmount").value;

    const category =
        document.getElementById("foodCategory").value;

    if (name === "") {

        alert("食材名を入力してください");
        return;

    }

    const list =
        document.getElementById("foodList");

    const row =
        document.createElement("div");

    row.className = "food-row";

    row.innerHTML = `
        <span>
            ${name} (${amount})
            - ${category}
        </span>
    `;

    list.appendChild(row);

    document.getElementById("foodName").value = "";
    document.getElementById("foodAmount").value = "";

}