/**
 * Mail Room artifact registry.
 * Transcriptions are supplied editorially — preserve historical spelling exactly.
 * When transcription is null, browse metadata still renders; reading is unavailable.
 *
 * Data fields (WordPress-ready):
 * - id, from, to, date, transcription, excerpt, excerptLabel
 * - presentation: historical-paper | digital-message | contemporary-letter
 * - provenance: documented-historical | constructed-creative | authored-noel | visitor-submission
 * - placement, group (optional browse grouping), afterLetter, psConnection
 *
 * Presentation is explicit in data — never inferred from date.
 */
window.MAIL_ROOM_ARTIFACTS = [
  {
    id: "wheatley-occom-1774",
    from: "Phillis Wheatley",
    to: "Rev. Samson Occom",
    date: "11 Feb 1774",
    excerpt: true,
    placement: "a",
    presentation: "historical-paper",
    transcription:
      "Rev'd and Honor'd Sir,\n\nGod grant deliverance in his own Way and Time, and get him honor upon all those whose Avarice impels them to countenance and help forward the Calamities of their Fellow Creatures. This I desire not for their Hurt, but to convince them of the strange Absurdity of their Conduct whose Words and Actions are so diametrically opposite. How well the Cry for Liberty, and the reverse disposition for the Exercise of Oppressive Power over others agree, — I humbly think it does not require the Penetration of a Philosopher to determine.",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "john-boston-elizabeth-1862",
    from: "John Boston",
    to: "Elizabeth Boston",
    date: "12 Jan 1862",
    excerpt: false,
    placement: "b",
    presentation: "historical-paper",
    transcription:
      "Upton Hill Va January the 12 1862\n\nMy Dear Wife it is with grate joy I take this time to let you know Whare I am i am now in Safety in the 14th Regiment of Brooklyn this Day i can Adress you thank god as a free man I had a little truble in giting away But as the lord led the Children of Isrel to the land of Canon So he led me to a land Whare fredom Will rain in spite Of earth and hell Dear you must make your Self content i am free from al the Slavers Lash and as you have chose the Wise plan Of Serving the lord i hope you Will pray Much and i Will try by the help of god To Serv him With all my hart I am With a very nice man and have All that hart Can Wish But My Dear I Cant express my grate desire that i Have to See you i trust the time Will Come When We Shal meet again And if We dont met on earth We Will Meet in heven Whare Jesas ranes Dear Elizabeth tell Mrs Own[ees] That i trust that She Will Continue Her kindness to you and that god Will Bless her on earth and Save her In grate eternity My Acomplements To Mrs Owens and her Children may They Prosper through life I never Shall forgit her kindness to me Dear Wife i must Close rest yourself Contented i am free i Want you to rite To me Soon as you Can Without Delay Direct your letter to the 14th Reigment New york State malitia Uptons Hill Virginea In Care of Mr Cranford Comary Write my Dear Soon As you C Your Affectionate Husban Kiss Daniel For me\n\nJohn Boston\n\nGive my love to Father and Mother",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "hannah-johnson-lincoln-1863",
    from: "Hannah Johnson",
    to: "Abraham Lincoln",
    date: "31 July 1863",
    excerpt: false,
    placement: "c",
    presentation: "historical-paper",
    transcription:
      "Buffalo July 31 1863\n\nExcellent Sir My good friend says I must write to you and she will send it My son went in the 54th regiment. I am a colored woman and my son was strong and able as any to fight for his country and the colored people have as much to fight for as any. My father was a Slave and escaped from Louisiana before I was born morn forty years agone I have but poor edication but I never went to schol, but I know just as well as any what is right between man and man. Now I know it is right that a colored man should go and fight for his country, and so ought to a white man. I know that a colored man ought to run no greater risques than a white, his pay is no greater his obligation to fight is the same. So why should not our enemies be compelled to treat him the same, Made to do it.\n\nMy son fought at Fort Wagoner but thank God he was not taken prisoner, as many were I thought of this thing before I let my boy go but then they said Mr. Lincoln will never let them sell our colored soldiers for slaves, if they do he will get them back quck he will rettallyate and stop it. Now My Lincoln dont you oght to stop this thing and make them do the same by the colored men they have lived in idleness all their lives on stolen labor and made savages of the colored people, but they now are so furious because they are proving themselves to be men, such as have come away and got some edication. It must not be so. You must put the rebels to work in State prisons to making shoes and things, if they sell our colored soldiers, till they let them all go. And give their wounded the same treatment. it would seem cruel, but their no other way, and a just man must do hard things sometimes, that shew him to be a great man.\n\nThey tell me some do you will take back the Proclamation, don't do it. When you are dead and in Heaven, in a thousand years that action of yours will make the Angels sing your praises I know it. Ought one man to own another, law for or not, who made the law, surely the poor slave did not. so it is wicked, and a horrible Outrage, there is no sense in it, because a man has lived by robbing all his life and his father before him, should he complain because the stolen things found on him are taken. Robbing the colored people of their labor is but a small part of the robbery their souls are almost taken, they are made bruits of often. You know all about this\n\nWill you see that the colored men fighting now, are fairly treated. You ought to do this, and do it at once, Not let the thing run along meet it quickly and manfully, and stop this, mean cowardly cruelty. We poor oppressed ones, appeal to you, and ask fair play. Yours for Christs sake\n\nHannah Johnson.",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "spotswood-rice-children-1864",
    from: "Spotswood Rice",
    to: "his children",
    date: "3 Sept 1864",
    excerpt: false,
    placement: "d",
    presentation: "historical-paper",
    group: "spotswood-rice-1864",
    transcription:
      "My Children I take my pen in hand to rite you A few lines to let you know that I have not forgot you and that I want to see you as bad as ever now my Dear Children I want you to be contented with whatever may be your lots be assured that I will have you if it cost me my life on the 28th of the mounth. 8 hundred White and 8 hundred blacke solders expects to start up the rivore to Glasgow and above there thats to be jeneraled by a jeneral that will give me both of you when they Come I expect to be with, them and expect to get you both in return. Dont be uneasy my children I expect to have you. If Diggs dont give you up this Government will and I feel confident that I will get you Your Miss Kaitty said that I tried to steal you But I'll let her know that god never intended for man to steal his own flesh and blood. If I had no cofidence in God I could have confidence in her But as it is If I ever had any Confidence in her I have none now and never expect to have And I want her to remember if she meets me with ten thousand soldiers she will meet her enemy I once thought that I had some respect for them but now my respects is worn out and have no sympathy for Slaveholders. And as for her cristianantty I expect the Devil has Such in hell You tell her from me that She is the frist Christian that I ever hard say that aman could Steal his own child especially out of human bondage\n\nYou can tell her that She can hold to you as long as she can I never would expect to ask her again to let you come to me because I know that the devil has got her hot set againsts that that is write now my Dear children I am a going to close my letter to you Give my love to all enquiring friends tell them all that we are well and want to see them very much and Corra and Mary receive the greater part of it you sefves Oh! My Dear children how I do want to see you\n\nSpotswood Rice",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "spotswood-rice-diggs-1864",
    from: "Spotswood Rice",
    to: "Kittey Diggs",
    date: "3 Sept 1864",
    excerpt: false,
    placement: "e",
    presentation: "historical-paper",
    group: "spotswood-rice-1864",
    transcription:
      "I received a leteter from Cariline telling me that you say I tried to steal to plunder my child away from you now I want you to understand that mary is my Child and she is a God given rite of my own and you may hold on to hear as long as you can but I want you to remembor this one thing that the longor you keep my Child from me the longor you will have to burn in hell and the qwicer youll get their for we are now makeing up a bout one thoughsand blacke troops to Come up tharough and when we come wo be to Copperhood rabbels and to the Slaveholding rebbels I want you to understand kittey diggs that where ever you and I meets we are enmays to each orthere I offered once to pay you forty dollers for my own Child but I am glad now that you did not accept it Just hold on now as long as you can and the worse it will be for you now you call my children your property not so with me my Children is my own and I expect to get them this whole Government gives chear to me and you cannot help your self\n\nSpotswood Rice",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "jourdon-anderson-1865",
    from: "Jourdon Anderson",
    to: "Col. P. H. Anderson",
    date: "7 Aug 1865",
    excerpt: false,
    placement: "f",
    presentation: "historical-paper",
    transcription:
      "Dayton, Ohio, August 7, 1865.\n\nTo my old Master, Colonel P. H. Anderson, Big Spring, Tennessee.\n\nSir: I got your letter, and was glad to find that you had not forgotten Jourdon, and that you wanted me to come back and live with you again, promising to do better for me than anybody else can. I have often felt uneasy about you. I thought the Yankees would have hung you long before this, for harboring Rebs they found at your house. Although you shot at me twice before I let you, I did not want to hear of your being hurt, and am glad you are still living. It would do me good to go back to the dear old home again, and see Miss Mary and Miss Martha and Allen, Esther, Green, and Lee. Give my love to them all.\n\nI want to know particularly what the good chance is you propose to give me. I am doing tolerably well here. I get twenty-five dollars a month, with victuals and clothing; have a comfortable home for Mandy,—the folks call her Mrs. Anderson,—and the children—Milly, Jane, and Grundy—go to school and are learning well. The teacher says Grundy has a head for a preacher. They go to Sunday school, and Mandy and me attend church regularly. We are kindly treated.\n\nAs to my freedom, which you say I can have, there is nothing to be gained on that score, as I got my free papers in 1864. Mandy says she would be afraid to go back without some proof that you were disposed to treat us justly and kindly; and we have concluded to test your sincerity by asking you to send us our wages for the time we served you. I served you faithfully for thirty-two years, and Mandy twenty years. At twenty-five dollars a month for me, and two dollars a week for Mandy, our earnings would amount to eleven thousand six hundred and eighty dollars. Add to this the interest for the time our wages have been kept back, and deduct what you paid for our clothing, and three doctor's visits to me, and pulling a tooth for Mandy, and the balance will show what we are in justice entitled to. Please send the money by Adams's Express, in care of V. Winters, Esq., Dayton, Ohio.\n\nIn answering this letter, please state if there would be any safety for my Milly and Jane, who are now grown up, and both good-looking girls. You know how it was with poor Matilda and Catherine. I would rather stay here and starve—and die, if it come to that—than have my girls brought to shame by the violence and wickedness of their young masters. You will also please state if there has been any schools opened for the colored children in your neighborhood. The great desire of my life now is to give my children an education, and have them form virtuous habits.\n\nSay howdy to George Carter, and thank him for taking the pistol from you when you were shooting at me.\n\nFrom your old servant,\n\nJourdon Anderson",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "douglass-tubman-1868",
    from: "Frederick Douglass",
    to: "Harriet Tubman",
    date: "29 Aug 1868",
    excerpt: false,
    placement: "g",
    presentation: "historical-paper",
    transcription:
      "Rochester, August 29, 1868\n\nDear Harriet: I am glad to know that the story of your eventful life has been written by a kind lady, and that the same is soon to be published. You ask for what you do not need when you call upon me for a word of commendation. I need such words from you far more than you can need them from me, especially where your superior labors and devotion to the cause of the lately enslaved of our land are known as I know them. The difference between us is very marked. Most that I have done and suffered in the service of our cause has been in public, and I have received much encouragement at every step of the way. You, on the other hand, have labored in a private way. I have wrought in the day — you in the night. I have had the applause of the crowd and the satisfaction that comes of being approved by the multitude, while the most that you have done has been witnessed by a few trembling, scarred, and foot-sore bondmen and women, whom you have led out of the house of bondage, and whose heartfelt \"God bless you\" has been your only reward. The midnight sky and the silent stars have been the witnesses of your devotion to freedom and of your heroism. Excepting John Brown — of sacred memory — I know of no one who has willingly encountered more perils and hardships to serve our enslaved people than you have. Much that you have done would seem improbable to those who do not know you as I know you. It is to me a great pleasure and a great privilege to bear testimony for your character and your works, and to say to those to whom you may come, that I regard you in every way truthful and trustworthy.\n\nYour friend,\n\nFrederick Douglass.",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "maya-mail-room-2026",
    from: "Maya",
    to: "The Mail Room",
    date: "14 AUG 2026",
    excerpt: false,
    placement: "h",
    presentation: "digital-message",
    provenance: "constructed-creative",
    group: "constructed-mail-room-2026",
    transcription:
      "I miss websites.\n\nNot the internet. Websites.\n\nI miss clicking on somebody's name and ending up somewhere that looked like them. Bad colors, weird fonts, a page about their cat, twenty-seven links to things they thought were interesting.\n\nYou could tell a person had been there.\n\nNow I open five different apps and somehow everything looks like the same room with different people standing in it.\n\nMaybe that's why I stayed on this site longer than I meant to.\n\nAnyway. I don't really have a question.\n\nI just wanted to say I noticed.\n\nMaya",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "r-mail-room-2026",
    from: "R.",
    to: "The Mail Room",
    date: "28 AUG 2026",
    excerpt: false,
    placement: "i",
    presentation: "digital-message",
    provenance: "constructed-creative",
    group: "constructed-mail-room-2026",
    transcription:
      "I disagree with you about curiosity.\n\nOr maybe I disagree with the way people romanticize it.\n\nCuriosity isn't always beautiful. Sometimes you look into something and wish you hadn't. Sometimes learning more about somebody makes it harder to love them. Sometimes the answer ruins the question.\n\nI think there should be room for that here too.\n\nWhat happens when curiosity costs you something?\n\nR.",
    afterLetter: null,
    psConnection: null,
  },
  {
    id: "noel-dad-2026",
    from: "Noèl",
    to: "Dad",
    date: "2 SEPT 2026",
    excerpt: false,
    placement: "j",
    presentation: "contemporary-letter",
    provenance: "authored-noel",
    transcription:
      "Dad,\n\nThey say a lot can change in a day.\n\nSo it seems obvious to say that a lot can change in 5,844 days.\n\nExcept I realized today that it has actually been 5,845 days since I last saw you.\n\nSeptember 2 was 5,844 days ago. But I didn't see you that morning before you left for work.\n\nYou got up, as you had done so many mornings before, and went to work.\n\nIt was routine. It was just another day.\n\nYou were supposed to come home, as you had every other day before.\n\nWe were not supposed to answer the door and see two police officers standing on our porch.\n\nFor years after your accident, one of the police officers would bring Amazon packages to our house that had accidentally been delivered to his. Mom would always comment on how nice he and his wife were for doing that. I thought so too.\n\nThen one day, I was the one who answered the door.\n\nI looked him in the eyes and recognized him.\n\nHe was one of the officers who had stood on our porch that day.\n\nHe had aged. The years had left their mark, and I'm sure the job had too. But I knew those eyes.\n\nI'll never forget those eyes.\n\n5,845 days.\n\nI didn't know how I was going to get through the next minute without you. So I told myself: just get through this minute.\n\nMinute by minute.\n\nAnd I guess that's what I've done.\n\nIn all the time since I last saw you, I can't say everything has been bad. Some really amazing things have happened in my life. Things that make me wonder what it would feel like to have you here experiencing them alongside me.\n\nI've fallen in love with a great man who reminds me so much of you.\n\nYou have a beautiful, smart granddaughter named Dallas, whose smile reminds me of yours.\n\nShe's so smart, Daddy.\n\nShe loves music and dancing, just like I did when I was a little girl.\n\nI watch her in wonder, and now I finally understand that look you had when you looked at me and Candi.\n\nThat mixture of love and pride that I could see on your face.\n\nI get it now.\n\n5,845 days.\n\nThat's a lot of days without you, Dad.\n\nBut thanks to God, I still feel you in them.\n\nYou never left my side.",
    afterLetter: null,
    psConnection: null,
  },
];
