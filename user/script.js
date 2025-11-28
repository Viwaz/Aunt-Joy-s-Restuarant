// Fetch data from the PHP API
fetch("get_menu.php")
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById("menu-container");

        container.innerHTML = ""; // Clear placeholder

        data.forEach(category => {
            // CATEGORY TITLE
            const title = document.createElement("h2");
            title.textContent = category.category;
            title.style.color = "#1a73e8";
            container.appendChild(title);

            // WRAPPER FOR MEALS
            const mealWrap = document.createElement("div");
            mealWrap.style.display = "flex";
            mealWrap.style.flexWrap = "wrap";
            mealWrap.style.gap = "20px";

            category.meals.forEach(meal => {
                const card = document.createElement("div");
                card.style.width = "250px";
                card.style.padding = "15px";
                card.style.border = "1px solid #ccc";
                card.style.borderRadius = "8px";
                card.style.boxShadow = "0 2px 5px rgba(0,0,0,0.1)";

                card.innerHTML = `
                    <img src="uploads/${meal.image}" 
                         style="width:100%; height:150px; object-fit:cover; border-radius:8px;">
                    <h3>${meal.name}</h3>
                    <p>${meal.description}</p>
                    <strong>MWK ${meal.price}</strong>
                `;

                mealWrap.appendChild(card);
            });

            container.appendChild(mealWrap);
        });
    })
    .catch(error => {
        document.getElementById("menu-container").textContent = "Failed to load menu.";
        console.error(error);
    });


    //new data
function fetchMenu() {
    fetch("fetch_menu.php")
        .then(res => res.json())
        .then(data => {
            console.log("Fetched Data:", data);
            displayMenu(data);
        })
        .catch(err => console.error(err));
}

function displayMenu(menuData) {
    const container = document.getElementById("menuContainer");
    container.innerHTML = ""; 

    menuData.forEach(category => {
        // Category Title
        container.innerHTML += `
            <h3 class="mt-4 mb-3">${category.category}</h3>
            <div class="row" id="cat-${category.category.replace(/\s/g, '')}"></div>
        `;

        const row = document.getElementById(`cat-${category.category.replace(/\s/g, '')}`);

        category.meals.forEach(item => {
            const card = `
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <img src="${item.image}" class="card-img-top" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">${item.name}</h5>
                            <p class="card-text">${item.description}</p>
                            <p class="text-primary fw-bold">MK ${item.price}</p>
                        </div>
                    </div>
                </div>
            `;
            row.innerHTML += card;
        });
    });
}

// Load menu on page start
window.onload = fetchMenu;
