<script setup>
</script>

<template>
  <v-container class="py-12" style="max-width: 860px">
    <h1 class="text-h4 font-weight-bold mb-2">Politique de Confidentialité</h1>
    <p class="text-caption text-grey mb-8">Dernière mise à jour : juin 2026</p>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">1. Responsable du traitement</h2>
      <p class="text-body-2 text-grey-darken-1">
        DariPets, plateforme de garde d'animaux au Maroc, est responsable du traitement de vos données personnelles.
        Contact DPO : <strong>privacy@daripets.ma</strong>
      </p>
    </v-card>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">2. Données collectées</h2>
      <p class="text-body-2 text-grey-darken-1 mb-3">Nous collectons les données suivantes :</p>
      <v-table density="compact">
        <thead>
          <tr>
            <th>Type de donnée</th>
            <th>Finalité</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>Nom, prénom, e-mail</td><td>Création et gestion du compte</td></tr>
          <tr><td>Numéro de téléphone</td><td>Contact et vérification</td></tr>
          <tr><td>Ville, adresse</td><td>Mise en relation géographique</td></tr>
          <tr><td>Photo de profil</td><td>Identification sur la plateforme</td></tr>
          <tr><td>Données de paiement (CMI)</td><td>Traitement des transactions (non stockées chez DariPets)</td></tr>
          <tr><td>Historique des réservations</td><td>Gestion des services et avis</td></tr>
        </tbody>
      </v-table>
    </v-card>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">3. Base légale</h2>
      <p class="text-body-2 text-grey-darken-1">
        Le traitement de vos données est fondé sur l'exécution du contrat (CGV) que vous avez accepté lors de
        votre inscription, conformément à la <strong>Loi 09-08</strong> relative à la protection des personnes
        physiques à l'égard du traitement des données à caractère personnel au Maroc.
      </p>
    </v-card>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">4. Partage des données</h2>
      <p class="text-body-2 text-grey-darken-1">
        Vos données ne sont jamais vendues à des tiers. Elles peuvent être partagées avec :
      </p>
      <ul class="text-body-2 text-grey-darken-1 pl-4 mt-2">
        <li class="mb-1">Le <strong>Centre Monétique Interbancaire (CMI)</strong> pour le traitement des paiements.</li>
        <li class="mb-1">Nos hébergeurs techniques (<strong>Supabase, Render, Vercel</strong>) dans le cadre de l'exploitation du service.</li>
        <li class="mb-1">Les autorités compétentes en cas d'obligation légale.</li>
      </ul>
    </v-card>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">5. Durée de conservation</h2>
      <p class="text-body-2 text-grey-darken-1">
        Vos données sont conservées pendant toute la durée de votre compte actif, puis <strong>3 ans</strong>
        après sa suppression pour des raisons légales et comptables.
      </p>
    </v-card>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">6. Vos droits</h2>
      <p class="text-body-2 text-grey-darken-1 mb-2">Conformément à la Loi 09-08, vous disposez des droits suivants :</p>
      <ul class="text-body-2 text-grey-darken-1 pl-4">
        <li class="mb-1"><strong>Droit d'accès</strong> : obtenir une copie de vos données.</li>
        <li class="mb-1"><strong>Droit de rectification</strong> : corriger des données inexactes.</li>
        <li class="mb-1"><strong>Droit d'opposition</strong> : vous opposer à certains traitements.</li>
        <li class="mb-1"><strong>Droit à l'effacement</strong> : demander la suppression de votre compte.</li>
      </ul>
      <p class="text-body-2 text-grey-darken-1 mt-3">
        Pour exercer vos droits : <strong>privacy@daripets.ma</strong>
      </p>
    </v-card>

    <v-card class="pa-8 mb-6" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">7. Cookies</h2>
      <p class="text-body-2 text-grey-darken-1">
        DariPets n'utilise pas de cookies tiers à des fins publicitaires. Seuls des cookies techniques
        nécessaires au bon fonctionnement de la plateforme (session, préférences) sont utilisés.
      </p>
    </v-card>

    <v-card class="pa-8" flat border>
      <h2 class="text-h6 font-weight-bold mb-3">8. Contact</h2>
      <p class="text-body-2 text-grey-darken-1">
        Pour toute question : <strong>privacy@daripets.ma</strong><br>
        CNDP (Commission Nationale de contrôle de la protection des Données à caractère Personnel) :
        <strong>www.cndp.ma</strong>
      </p>
    </v-card>
  </v-container>
</template>
